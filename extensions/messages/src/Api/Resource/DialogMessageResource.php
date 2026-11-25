<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Api\Resource;

use Exception;
use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Bus\Dispatcher;
use Flarum\Database\IntegerKey;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\ErrorHandling\LogReporter;
use Flarum\Foundation\ValidationException;
use Flarum\Group\Group;
use Flarum\Locale\Translator;
use Flarum\Messages\Access\MessagingPermission;
use Flarum\Messages\Command\ReadDialog;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogAudience;
use Flarum\Messages\DialogMessage;
use Flarum\Messages\RecipientGroups;
use Flarum\Messages\RecipientLimit;
use Flarum\User\User;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Tobyz\JsonApiServer\Context as OriginalContext;
use Tobyz\JsonApiServer\Exception\BadRequestException;
use WeakMap;

/**
 * @extends Resource\AbstractDatabaseResource<DialogMessage>
 */
class DialogMessageResource extends Resource\AbstractDatabaseResource
{
    /**
     * Who each new message is to, from validating it to saving it.
     *
     * @var WeakMap<DialogMessage, int[]>|null
     */
    protected ?WeakMap $recipients = null;

    /** @var WeakMap<DialogMessage, int[]>|null */
    protected ?WeakMap $recipientGroups = null;

    /**
     * Who could see the message, before deleting it may take its conversation,
     * and the conversation's members, with it.
     *
     * @var WeakMap<DialogMessage, int[]>|null
     */
    protected ?WeakMap $deletedFrom = null;

    public function __construct(
        protected Translator $translator,
        protected LogReporter $log,
        protected Dispatcher $bus,
        protected ExtensionManager $extensions,
        protected ConnectionInterface $db,
        protected RecipientLimit $recipientLimit,
        protected RecipientGroups $groups,
    ) {
    }

    public function type(): string
    {
        return 'dialog-messages';
    }

    public function model(): string
    {
        return DialogMessage::class;
    }

    public function scope(Builder $query, OriginalContext $context): void
    {
        $query->whereVisibleTo($context->getActor());
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Create::make()
                ->authenticated()
                ->eagerLoadWhenIncluded(['dialog' => ['dialog.users', 'dialog.state', 'dialog.groups'], 'dialog.users' => ['dialog.users.groups']])
                ->visible(function (Context $context): bool {
                    $actor = $context->getActor();

                    $dialogId = (int) Arr::get($context->body(), 'data.relationships.dialog.data.id');

                    // If this is a new dialog instance, the user must have permission to
                    // start new dialogs. Otherwise, they must have access to send messages in
                    // this dialog.
                    if ($dialogId) {
                        $dialog = Dialog::whereVisibleTo($context->getActor())->findOrFail($dialogId);

                        return $actor->can('sendMessage', $dialog);
                    } else {
                        return $actor->can('sendAnyMessage');
                    }
                }),
            // Edit, delete in a way that can be undone, and restore.
            Endpoint\Update::make()
                ->authenticated()
                ->visible(function (DialogMessage $message, Context $context): bool {
                    $actor = $context->getActor();

                    return $actor->can('edit', $message) || $actor->can('hide', $message);
                })
                ->defaultInclude(['editedUser', 'hiddenUser']),
            // Delete for good.
            Endpoint\Delete::make()
                ->authenticated()
                ->visible(function (DialogMessage $message, Context $context): bool {
                    return $context->getActor()->can('delete', $message);
                }),
            // The mention relationships are added to these defaults from
            // extend.php, only when the extensions that provide them are
            // enabled: naming `mentionsTags` on a forum without Tags makes every
            // bare request for a message fail with "Resource [tags] not found".
            //
            // With each message's conversation: its members, which its title
            // is made from, and the member's own read state; with its members,
            // their groups, which serialising each of them reads. Otherwise a
            // query per conversation, or per member.
            Endpoint\Show::make()
                ->authenticated()
                ->defaultInclude(['user', 'dialog', 'editedUser'])
                ->eagerLoadWhenIncluded(['dialog' => ['dialog.users', 'dialog.state', 'dialog.groups'], 'dialog.users' => ['dialog.users.groups']]),
            Endpoint\Index::make()
                ->authenticated()
                ->defaultInclude(['user', 'dialog', 'editedUser'])
                ->eagerLoadWhenIncluded(['dialog' => ['dialog.users', 'dialog.state', 'dialog.groups'], 'dialog.users' => ['dialog.users.groups']])
                ->defaultSort('-number')
                // Whether each message can be edited or deleted turns on its
                // conversation, and whether its members can still be written
                // to: included or not, once for the page, not per message.
                ->eagerLoad(function () {
                    $relations = ['dialog.users.groups'];

                    if (! $this->extensions->isEnabled('flarum-mentions')) {
                        return $relations;
                    }

                    $relations = [...$relations, 'mentionsUsers', 'mentionsPosts', 'mentionsGroups'];

                    if ($this->extensions->isEnabled('flarum-tags')) {
                        $relations[] = 'mentionsTags';
                    }

                    return $relations;
                })
                ->extractOffset(function (Context $context, array $defaultExtracts): int {
                    $queryParams = $context->request->getQueryParams();
                    $near = intval(Arr::get($queryParams, 'page.near'));

                    if ($near > 1) {
                        $sort = $defaultExtracts['sort'];
                        $filter = $defaultExtracts['filter'];
                        $dialogId = $filter['dialog'] ?? null;

                        if (count($filter) > 1 || ! $dialogId || is_array($dialogId) || ($sort && $sort !== ['number' => 'desc'])) {
                            throw new BadRequestException(
                                'You can only use page[near] with filter[dialog] and the default sort order'
                            );
                        }

                        $limit = $defaultExtracts['limit'];

                        $index = 0;

                        // No message number reaches one beyond the column's range.
                        if (IntegerKey::fits(new DialogMessage, trim((string) $dialogId), 'dialog_id') && IntegerKey::fits(new DialogMessage, $near, 'number')) {
                            $index = DialogMessage::query()
                                ->where('dialog_id', $dialogId)
                                ->where('number', '>=', $near)
                                ->orderBy('number', 'desc')
                                ->whereVisibleTo($context->getActor())
                                ->count();
                        }

                        return max(0, $index - intdiv($limit, 2));
                    }

                    return $defaultExtracts['offset'];
                })
                ->paginate(),
        ];
    }

    public function fields(): array
    {
        return [

            Schema\Number::make('number')
                ->description('Position of the message in its dialog, from 1.'),
            // A member's message, or a notice such as someone leaving.
            Schema\Str::make('type')
                ->description('"comment" for a member\'s message, or a notice: "userLeft" or "renamed".'),
            Schema\Arr::make('eventData')
                ->description('Details of a notice, such as who left or the new name. Absent on comments.')
                ->visible(fn (DialogMessage $message) => ! $message->isComment())
                ->get(fn (DialogMessage $message) => $message->eventData()),
            Schema\Str::make('content')
                ->description('The message, in the forum\'s input format (Markdown or BBCode, as enabled). Shown only to those who can edit it.')
                ->requiredOnCreate()
                ->writable(function (DialogMessage $message, Context $context) {
                    return $context->creating() || (
                        $context->updating()
                        && $message->isComment()
                        && $context->getActor()->can('edit', $message)
                    );
                })
                ->visible(fn (DialogMessage $message, Context $context) => $message->isComment() && $context->getActor()->can('edit', $message))
                ->minLength(1)
                // Blank strings skip minLength; an edit can't empty a message.
                ->rule('filled', fn (Context $context) => $context->updating())
                ->maxLength(63000)
                ->set(function (DialogMessage $message, string $value, Context $context) {
                    if ($context->creating()) {
                        $message->setContentAttribute($value, $context->getActor());
                    } else {
                        $message->revise($value, $context->getActor());
                    }
                }),
            Schema\Str::make('contentHtml')
                ->description('The message rendered as HTML. Absent on a deleted message, except for its author and those who can restore it.')
                ->visible(fn (DialogMessage $message, Context $context) => $message->isComment() && $this->canSeeContent($message, $context->getActor()))
                ->get(function (DialogMessage $post, Context $context) {
                    try {
                        $rendered = $post->formatContent($context->request);
                        $post->setAttribute('renderFailed', false);
                    } catch (Exception $e) {
                        $rendered = $this->translator->trans('core.lib.error.render_failed_message');
                        $this->log->report($e);
                        $post->setAttribute('renderFailed', true);
                    }

                    return $rendered;
                }),
            Schema\Boolean::make('renderFailed'),
            Schema\DateTime::make('createdAt'),
            Schema\DateTime::make('editedAt')
                ->description('When the message was last edited, or null if it never was.'),
            Schema\Boolean::make('isHidden')
                ->description('Whether the message is deleted, in a way that can be undone. Set to true to delete it, false to restore it.')
                ->get(fn (DialogMessage $message) => $message->isHidden())
                // A notice records what happened, whoever may hide messages.
                ->writable(fn (DialogMessage $message, Context $context) => $context->updating() && $message->isComment() && $context->getActor()->can('hide', $message))
                ->set(function (DialogMessage $message, bool $value, Context $context) {
                    $value ? $message->hide($context->getActor()) : $message->restore($context->getActor());
                }),
            Schema\DateTime::make('hiddenAt')
                ->visible(fn (DialogMessage $message) => $message->isHidden()),
            Schema\Str::make('ipAddress')
                ->description('The IP address the message was sent from. Only visible to those allowed to view IP addresses.')
                ->visible(fn (DialogMessage $message, Context $context) => $context->getActor()->can('dialog.viewIps')),

            // Write-only.
            Schema\Arr::make('users')
                ->description('Write-only, to start a new dialog: the recipients, as a list of {id} objects. Give users and/or groups, or the dialog relationship.')
                ->writableOnCreate()
                ->hidden()
                ->set(fn () => null),

            Schema\Arr::make('groups')
                ->writableOnCreate()
                ->hidden()
                ->set(fn () => null),

            // Read-only.
            Schema\Boolean::make('canDelete')
                ->get(function (DialogMessage $message, Context $context) {
                    return $context->getActor()->can('delete', $message);
                }),
            Schema\Boolean::make('canEdit')
                ->get(fn (DialogMessage $message, Context $context) => $context->getActor()->can('edit', $message)),
            Schema\Boolean::make('canHide')
                ->get(fn (DialogMessage $message, Context $context) => $context->getActor()->can('hide', $message)),

            Schema\Relationship\ToOne::make('user')
                ->type('users')
                ->includable(),
            Schema\Relationship\ToOne::make('editedUser')
                ->description('Who last edited the message.')
                ->type('users')
                ->includable(),
            Schema\Relationship\ToOne::make('hiddenUser')
                ->description('Who deleted the message.')
                ->type('users')
                ->includable()
                ->visible(fn (DialogMessage $message) => $message->isHidden()),
            Schema\Relationship\ToOne::make('dialog')
                ->description('The dialog the message is in. When sending, give this or the users/groups attributes.')
                ->type('dialogs')
                ->includable()
                ->writableOnCreate(),
            // What a deleted message mentioned is part of what it said.
            Schema\Relationship\ToMany::make('mentionsUsers')
                ->type('users')
                ->includable()
                ->visible(fn (DialogMessage $message, Context $context) => $this->canSeeContent($message, $context->getActor())),
            Schema\Relationship\ToMany::make('mentionsPosts')
                ->type('posts')
                ->includable()
                ->visible(fn (DialogMessage $message, Context $context) => $this->canSeeContent($message, $context->getActor())),
            Schema\Relationship\ToMany::make('mentionsGroups')
                ->type('groups')
                ->includable()
                ->visible(fn (DialogMessage $message, Context $context) => $this->canSeeContent($message, $context->getActor())),
            Schema\Relationship\ToMany::make('mentionsTags')
                ->type('tags')
                ->includable()
                ->visible(fn (DialogMessage $message, Context $context) => $this->canSeeContent($message, $context->getActor())),

        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('number'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function creating(object $model, OriginalContext $context): ?object
    {
        $actor = $context->getActor();

        $model->user_id = $actor->id;
        $model->ip_address = $context->request->getAttribute('ipAddress');
        $data = $context->body()['data'] ?? [];

        $this->events->dispatch(
            new DialogMessage\Event\Creating($model, $data)
        );

        if (! $model->dialog_id) {
            $actor->assertCan('sendAnyMessage');

            $users = array_values(array_unique(array_map(
                'intval',
                array_filter(Arr::pluck($data['attributes']['users'] ?? [], 'id'), fn (mixed $id) => $id && (int) $id !== $actor->id)
            )));

            $inputGroups = $data['attributes']['groups'] ?? [];
            foreach ($inputGroups as $group) {
                $id = is_array($group) ? ($group['id'] ?? null) : null;
                if ((! is_int($id) && ! is_string($id)) || ! preg_match('/^[1-9][0-9]*$/D', (string) $id)) {
                    throw new ValidationException(['groups' => $this->translator->trans('flarum-messages.lib.groups_unavailable_message')]);
                }
            }
            $groups = array_values(array_unique(array_map('intval', Arr::pluck($inputGroups, 'id'))));
            if (count($groups) > RecipientGroups::LIMIT) {
                throw new ValidationException(['groups' => $this->translator->trans('flarum-messages.lib.too_many_groups_message', ['count' => RecipientGroups::LIMIT])]);
            }
            if ($groups) {
                $actor->assertCan(RecipientGroups::PERMISSION);
                $found = Group::whereVisibleTo($actor)->whereIn('id', $groups)->get();
                if ($found->count() !== count($groups) || $found->contains(fn ($group) => ! $this->groups->canAddress($actor, $group))) {
                    throw new ValidationException(['groups' => $this->translator->trans('flarum-messages.lib.groups_unavailable_message')]);
                }
            }

            // Before anything is looked up: a message can't reach more members
            // than the forum allows.
            if (count($users) > ($limit = $this->recipientLimit->get())) {
                throw new ValidationException([
                    'users' => $this->translator->trans('flarum-messages.lib.too_many_recipients_message', ['count' => $limit]),
                ]);
            }

            // Checked before anything is written: a recipient who doesn't exist
            // used to reach the database, fail on the foreign key, and leave a
            // dialog with no messages behind.
            if ((! $groups && empty($users)) || User::whereVisibleTo($actor)->whereIn('id', $users)->count() !== count($users)) {
                throw new ValidationException([
                    'users' => str_replace(':attribute', 'users', $this->translator->trans('validation.exists')),
                ]);
            }

            $this->assertRecipientsCanBeMessaged($actor, $users, group: (bool) $groups || count($users) > 1);

            // Written with the message, in create(): a message that fails to
            // save must not leave a conversation with nothing in it behind.
            $this->recipients ??= new WeakMap();
            $this->recipients[$model] = $users;
            $this->recipientGroups ??= new WeakMap();
            $this->recipientGroups[$model] = $groups;
        } elseif ($model->dialog && DialogAudience::users($model->dialog)->where('users.id', '!=', $actor->id)->doesntExist()) {
            // The other member has left; there is no one to send to.
            throw new ValidationException([
                'dialog' => $this->translator->trans('flarum-messages.lib.no_other_members_message'),
            ]);
        }

        return parent::creating($model, $context);
    }

    /**
     * The conversation a new message starts or continues, its members, and the
     * message, together or not at all. Core's create flow has no transaction
     * of its own.
     *
     * @inheritDoc
     */
    public function create(object $model, OriginalContext $context): object
    {
        return $this->db->transaction(function () use ($model, $context) {
            if (isset($this->recipients[$model])) {
                $users = $this->recipients[$model];
                $model->dialog()->associate($this->dialogFor($model, $users, $context->getActor(), $this->recipientGroups[$model] ?? []));
            }

            return parent::create($model, $context);
        });
    }

    /**
     * One recipient: the conversation the pair already have, if any. More: a
     * group, new every time, as a group is about something and the same
     * people may have several.
     *
     * @param int[] $users
     */
    protected function dialogFor(DialogMessage $model, array $users, User $actor, array $groups = []): Dialog
    {
        $dialog = ! $groups && count($users) === 1 ? Dialog::for($model, $users) : Dialog::startGroup();

        $dialog->groups()->attach($groups);

        // Only members not yet in the dialog are added: syncing them all
        // would reset `joined_at` for the ones already there.
        $members = $dialog->users()->pluck('users.id')->all();
        $joining = array_diff([...$users, $actor->id], $members);

        if ($joining) {
            $dialog->users()->attach(array_fill_keys($joining, ['joined_at' => Carbon::now()]));
        }

        if (! $dialog->first_message_id) {
            $dialog->recipient_count = max(0, DialogAudience::users($dialog)->count() - 1);
            $dialog->save();
        }

        return $dialog;
    }

    /**
     * @inheritDoc
     */
    public function created(object $model, OriginalContext $context): ?object
    {
        // Refresh to replace the DB Expression used for atomic message numbering
        // (set during the Eloquent creating event) with the actual integer value.
        // Without this, serializing the response throws a type cast error:
        // "Object of class Expression could not be converted to int".
        $model->refresh();

        if ($model->dialog->last_message_id !== $model->id) {
            $model->dialog->setLastMessage($model);
        }

        if (! $model->dialog->first_message_id) {
            // Normally this is the first message of a new dialog. It can also be
            // a dialog whose first message pointer went missing, though, and
            // that one starts at its oldest surviving message, not at this one.
            $model->dialog->setFirstMessage($model->dialog->messages()->oldest('id')->first() ?? $model);
        }

        // Someone allowed to message users without messaging permission has
        // spoken here, so whoever they're talking to can answer.
        if (! $model->dialog->anyone_can_reply && MessagingPermission::canMessageUsersWithoutPermission($context->getActor())) {
            $model->dialog->anyone_can_reply = true;
        }

        $model->dialog->isDirty() && $model->dialog->save();

        $this->bus->dispatch(
            new ReadDialog($model->dialog_id, $context->getActor(), $model->id)
        );

        $this->events->dispatch(
            new DialogMessage\Event\Created($model)
        );

        return parent::created($model, $context);
    }

    /**
     * Nobody can message someone another extension rules out: flarum/gdpr does
     * for anonymised accounts. And a new dialog's recipients must be able to
     * reply, unless the actor may message users without messaging permission
     * or, for a direct message, the dialog the pair already share has been
     * opened to replies. A new group is opened by nobody yet.
     *
     * @param int[] $userIds
     */
    protected function assertRecipientsCanBeMessaged(User $actor, array $userIds, bool $group = false): void
    {
        $canMessageUsersWithoutPermission = MessagingPermission::canMessageUsersWithoutPermission($actor);

        // With their groups: whether each can reply is read off them.
        foreach (User::query()->with('groups')->whereIn('id', $userIds)->get() as $recipient) {
            if ($actor->cannot('message', $recipient)) {
                throw new ValidationException([
                    'users' => $this->translator->trans('flarum-messages.lib.recipient_unavailable_message', ['username' => $recipient->display_name]),
                ]);
            }

            if ($canMessageUsersWithoutPermission || MessagingPermission::canReply($recipient)) {
                continue;
            }

            $opened = ! $group && Dialog::query()
                ->where('type', Dialog::DIRECT)
                ->where('anyone_can_reply', true)
                ->whereRelation('users', 'user_id', $actor->id)
                ->whereRelation('users', 'user_id', $recipient->id)
                ->exists();

            if (! $opened) {
                throw new ValidationException([
                    'users' => $this->translator->trans('flarum-messages.lib.recipient_cannot_reply_message', ['username' => $recipient->display_name]),
                ]);
            }
        }
    }

    /**
     * A deleted message's content is its author's, and theirs who may restore
     * it; the other members see that it was deleted.
     */
    protected function canSeeContent(DialogMessage $message, User $actor): bool
    {
        return ! $message->isHidden()
            || $message->user_id === $actor->id
            || $actor->can('hide', $message);
    }

    /**
     * @inheritDoc
     */
    public function deleting(object $model, OriginalContext $context): void
    {
        $this->deletedFrom ??= new WeakMap();
        $this->deletedFrom[$model] = $model->dialog
            ? DialogAudience::users($model->dialog)->pluck('users.id')->map(fn ($id) => (int) $id)->all()
            : [];

        $this->events->dispatch(
            new DialogMessage\Event\Deleting($model, $context->getActor())
        );

        parent::deleting($model, $context);
    }

    /**
     * @inheritDoc
     */
    public function deleted(object $model, OriginalContext $context): void
    {
        $dialogId = (int) $model->dialog_id;

        $this->events->dispatch(
            new DialogMessage\Event\Deleted(
                $model,
                $context->getActor(),
                $dialogId,
                $this->deletedFrom[$model] ?? [],
                ! Dialog::query()->whereKey($dialogId)->exists(),
            )
        );

        parent::deleted($model, $context);
    }

    /**
     * @inheritDoc
     */
    public function updating(object $model, OriginalContext $context): ?object
    {
        $this->events->dispatch(
            new DialogMessage\Event\Updating($model, $context->body()['data'] ?? [])
        );

        return parent::updating($model, $context);
    }

    /**
     * @inheritDoc
     */
    public function updated(object $model, OriginalContext $context): ?object
    {
        $this->events->dispatch(
            new DialogMessage\Event\Updated($model)
        );

        return parent::updated($model, $context);
    }
}
