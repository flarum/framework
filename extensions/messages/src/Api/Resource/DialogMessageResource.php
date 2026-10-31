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
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\ErrorHandling\LogReporter;
use Flarum\Foundation\ValidationException;
use Flarum\Locale\Translator;
use Flarum\Messages\Access\MessagingPermission;
use Flarum\Messages\Command\ReadDialog;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\User\User;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Tobyz\JsonApiServer\Context as OriginalContext;
use Tobyz\JsonApiServer\Exception\BadRequestException;

/**
 * @extends Resource\AbstractDatabaseResource<DialogMessage>
 */
class DialogMessageResource extends Resource\AbstractDatabaseResource
{
    public function __construct(
        protected Translator $translator,
        protected LogReporter $log,
        protected Dispatcher $bus,
        protected ExtensionManager $extensions,
        protected ConnectionInterface $db,
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
            Endpoint\Delete::make()
                ->authenticated()
                ->visible(function (DialogMessage $message, Context $context): bool {
                    return $context->getActor()->can('delete', $message);
                }),
            // The mention relationships are added to these defaults from
            // extend.php, only when the extensions that provide them are
            // enabled: naming `mentionsTags` on a forum without Tags makes every
            // bare request for a message fail with "Resource [tags] not found".
            Endpoint\Show::make()
                ->authenticated()
                ->defaultInclude(['user', 'dialog']),
            Endpoint\Index::make()
                ->authenticated()
                ->defaultInclude(['user', 'dialog'])
                ->defaultSort('-number')
                ->eagerLoad(function () {
                    if (! $this->extensions->isEnabled('flarum-mentions')) {
                        return [];
                    }

                    $relations = ['mentionsUsers', 'mentionsPosts', 'mentionsGroups'];

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

                        $index = DialogMessage::query()
                            ->where('dialog_id', $dialogId)
                            ->where('number', '>=', $near)
                            ->orderBy('number', 'desc')
                            ->whereVisibleTo($context->getActor())
                            ->count();

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

            Schema\Number::make('number'),
            Schema\Str::make('content')
                ->requiredOnCreate()
                ->writableOnCreate()
                ->hidden()
                ->minLength(1)
                ->maxLength(63000)
                ->set(function (DialogMessage $post, string $value, Context $context) {
                    $post->setContentAttribute($value, $context->getActor());
                }),
            Schema\Str::make('contentHtml')
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
            Schema\Str::make('ipAddress')
                ->visible(fn (DialogMessage $message, Context $context) => $context->getActor()->can('dialog.viewIps')),

            // Write-only.
            Schema\Arr::make('users')
                ->requiredOnCreateWithout(['relationships.dialog'])
                ->writableOnCreate()
                ->hidden()
                ->items(1)
                ->set(fn () => null),

            // Read-only.
            Schema\Boolean::make('canDelete')
                ->get(function (DialogMessage $message, Context $context) {
                    return $context->getActor()->can('delete', $message);
                }),

            Schema\Relationship\ToOne::make('user')
                ->type('users')
                ->includable(),
            Schema\Relationship\ToOne::make('dialog')
                ->type('dialogs')
                ->includable()
                ->writableOnCreate()
                ->requiredOnCreateWithout(['attributes.users']),
            Schema\Relationship\ToMany::make('mentionsUsers')
                ->type('users')
                ->includable(),
            Schema\Relationship\ToMany::make('mentionsPosts')
                ->type('posts')
                ->includable(),
            Schema\Relationship\ToMany::make('mentionsGroups')
                ->type('groups')
                ->includable(),
            Schema\Relationship\ToMany::make('mentionsTags')
                ->type('tags')
                ->includable(),

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

            // Checked before anything is written: a recipient who doesn't exist
            // used to reach the database, fail on the foreign key, and leave a
            // dialog with no messages behind.
            if (empty($users) || User::whereVisibleTo($actor)->whereIn('id', $users)->count() !== count($users)) {
                throw new ValidationException([
                    'users' => str_replace(':attribute', 'users', $this->translator->trans('validation.exists')),
                ]);
            }

            $this->assertRecipientsCanBeMessaged($actor, $users);

            // The dialog and its members go in together, or not at all. Core's
            // create flow has no transaction of its own.
            $dialog = $this->db->transaction(function () use ($model, $users, $actor) {
                $dialog = Dialog::for($model, $users);

                // Only members not yet in the dialog are added: syncing them all
                // would reset `joined_at` for the ones already there.
                $members = $dialog->users()->pluck('users.id')->all();
                $joining = array_diff([...$users, $actor->id], $members);

                if ($joining) {
                    $dialog->users()->attach(array_fill_keys($joining, ['joined_at' => Carbon::now()]));
                }

                return $dialog;
            });

            $model->dialog()->associate($dialog);
        } elseif ($model->dialog && $model->dialog->users()->where('users.id', '!=', $actor->id)->doesntExist()) {
            // The other member has left; there is no one to send to.
            throw new ValidationException([
                'dialog' => $this->translator->trans('flarum-messages.lib.no_other_members_message'),
            ]);
        }

        return parent::creating($model, $context);
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
     * or the dialog they already share has been opened to replies.
     *
     * @param int[] $userIds
     */
    protected function assertRecipientsCanBeMessaged(User $actor, array $userIds): void
    {
        $canMessageUsersWithoutPermission = MessagingPermission::canMessageUsersWithoutPermission($actor);

        foreach (User::query()->whereIn('id', $userIds)->get() as $recipient) {
            if ($actor->cannot('message', $recipient)) {
                throw new ValidationException([
                    'users' => $this->translator->trans('flarum-messages.lib.recipient_unavailable_message', ['username' => $recipient->display_name]),
                ]);
            }

            if ($canMessageUsersWithoutPermission || MessagingPermission::canReply($recipient)) {
                continue;
            }

            $opened = Dialog::query()
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
