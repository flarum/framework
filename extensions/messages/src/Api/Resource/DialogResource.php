<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Api\Resource;

use Carbon\Carbon;
use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Bus\Dispatcher;
use Flarum\Locale\Translator;
use Flarum\Messages\Access\MessagingPermission;
use Flarum\Messages\Command\LeaveDialog;
use Flarum\Messages\Command\ReadDialog;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogEventMessages;
use Flarum\Messages\DialogMessage;
use Flarum\Messages\GroupNames;
use Flarum\Messages\UserDialogState;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Laminas\Diactoros\Response\EmptyResponse;
use Tobyz\JsonApiServer\Context as OriginalContext;

/**
 * @extends Resource\AbstractDatabaseResource<Dialog>
 */
class DialogResource extends Resource\AbstractDatabaseResource
{
    /** Members named in a group's title before the rest are counted. */
    protected const TITLE_NAMES = 3;

    public function __construct(
        protected Translator $translator,
        protected Dispatcher $bus,
        protected GroupNames $names,
        protected DialogEventMessages $eventMessages,
    ) {
    }

    public function type(): string
    {
        return 'dialogs';
    }

    public function model(): string
    {
        return Dialog::class;
    }

    public function scope(Builder $query, OriginalContext $context): void
    {
        $query->whereVisibleTo($context->getActor());
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Show::make()
                ->authenticated()
                ->eagerLoad(['state', 'users.groups']),
            Endpoint\Update::make()
                ->authenticated()
                ->eagerLoad('state'),
            Endpoint\Endpoint::make('read')
                ->route('POST', '/read')
                ->authenticated()
                ->action(function (Context $context) {
                    $connection = UserDialogState::query()->getConnection();
                    $grammar = UserDialogState::query()->getGrammar();

                    // Only rows with something unread, so a dialog with no
                    // messages yet (its last_message_id is null) is left alone
                    // rather than having null written into a NOT NULL column,
                    // which fails the whole statement on MySQL.
                    UserDialogState::query()
                        ->where('dialog_user.user_id', $context->getActor()->id)
                        ->whereExists(function ($query) {
                            $query->selectRaw('1')
                                ->from('dialogs')
                                ->whereColumn('dialogs.id', 'dialog_user.dialog_id')
                                ->whereColumn('dialogs.last_message_id', '>', 'dialog_user.last_read_message_id');
                        })
                        ->update([
                            'last_read_message_id' => $connection->raw('COALESCE(('.$grammar->compileSelect(
                                Dialog::query()
                                    ->select('last_message_id')
                                    ->from('dialogs')
                                    ->whereColumn('dialogs.id', 'dialog_user.dialog_id')
                                    ->toBase()
                            ).'), 0)'),
                            'last_read_at' => Carbon::now(),
                        ]);
                })
                ->response(fn () => new EmptyResponse(204)),
            Endpoint\Endpoint::make('leave')
                ->route('POST', '/{id}/leave')
                ->authenticated()
                ->can('leave')
                ->action(function (Context $context) {
                    $this->bus->dispatch(
                        new LeaveDialog((int) $context->modelId, $context->getActor())
                    );
                })
                ->response(fn () => new EmptyResponse(204)),
            Endpoint\Index::make()
                ->authenticated()
                ->defaultSort('-lastMessageAt')
                ->paginate()
                ->eagerLoad(['users.groups', 'state']),
        ];
    }

    public function fields(): array
    {
        return [

            Schema\Str::make('title')
                ->description('Generated from the other member\'s display name.')
                ->get(function (Dialog $dialog, Context $context) {
                    if ($dialog->isGroup()) {
                        return $this->names->of($dialog) ?? $this->groupTitle($dialog, $context->getActor());
                    }

                    // The other member may have left the forum since.
                    return $this->translator->trans('flarum-messages.lib.dialog.title', [
                        '{username}' => $dialog->recipient($context->getActor())->display_name
                            ?? $this->translator->trans('core.lib.username.deleted_text'),
                    ]);
                }),
            // What members have named a group, where the forum allows it.
            Schema\Str::make('name')
                ->nullable()
                ->maxLength(GroupNames::MAX_LENGTH)
                ->get(fn (Dialog $dialog) => $this->names->of($dialog))
                ->writable(fn (Dialog $dialog, Context $context) => $context->updating() && $this->names->canRename($context->getActor(), $dialog))
                ->set(function (Dialog $dialog, ?string $value, Context $context) {
                    $name = trim((string) $value) ?: null;

                    if ($name === $dialog->name) {
                        return;
                    }

                    $dialog->name = $name;

                    // The group is told who named it, and what.
                    $dialog->afterSave(function (Dialog $dialog) use ($name, $context) {
                        $notice = $this->eventMessages->post($dialog, $context->getActor(), DialogMessage::RENAMED, ['name' => $name]);

                        $this->events->dispatch(new DialogMessage\Event\Created($notice));
                    });
                }),
            Schema\Boolean::make('canRename')
                ->visible(fn (Dialog $dialog, Context $context) => $context->collection instanceof self)
                ->get(fn (Dialog $dialog, Context $context) => $this->names->canRename($context->getActor(), $dialog)),
            Schema\Str::make('type')
                ->description('The dialog type. direct is a conversation between members.')
                ->minLength(3)
                ->maxLength(255)
                ->in(Dialog::$types),
            Schema\DateTime::make('lastMessageAt'),
            Schema\DateTime::make('createdAt'),

            Schema\Integer::make('unreadCount')
                ->description('Number of messages you have not read.')
                ->countRelation('messages', function (Builder $query, Context $context) {
                    // Only what members wrote: a notice is nothing to read.
                    $query->leftJoin('dialog_user', 'dialog_messages.dialog_id', '=', 'dialog_user.dialog_id')
                        ->where('dialog_messages.type', DialogMessage::COMMENT)
                        ->where('dialog_user.user_id', $context->getActor()->id)
                        ->whereColumn('dialog_messages.id', '>', 'dialog_user.last_read_message_id')
                        ->groupBy('dialog_messages.dialog_id');
                }),
            Schema\DateTime::make('lastReadAt')
                ->visible(fn (Dialog $dialog) => $dialog->state !== null)
                ->get(function (Dialog $dialog) {
                    return $dialog->state->last_read_at;
                }),
            Schema\Integer::make('lastMessageId'),
            Schema\Boolean::make('anyoneCanReply')
                ->description('Whether members without messaging permission may reply.')
                ->get(fn (Dialog $dialog) => (bool) $dialog->anyone_can_reply),
            // Without the policy's own visibility query: a dialog serialised to
            // the actor is already one of theirs. Only where dialogs are what
            // was asked for, whose endpoints load the members' groups; a dialog
            // included with each of its messages would look them up every time.
            Schema\Boolean::make('canSendMessage')
                ->description('Whether you can send a message in this dialog.')
                ->visible(fn (Dialog $dialog, Context $context) => $context->collection instanceof self)
                ->get(fn (Dialog $dialog, Context $context) => MessagingPermission::canSendIn($context->getActor(), $dialog)),
            Schema\Integer::make('lastReadMessageId')
                ->description('ID of the last message you have read. Set it to mark the dialog as read up to that message.')
                ->visible(fn (Dialog $dialog) => $dialog->state !== null)
                ->get(function (Dialog $dialog) {
                    return $dialog->state?->last_read_message_id;
                })
                ->writableOnUpdate()
                ->set(function (Dialog $dialog, int $value, Context $context) {
                    // No further than the last message: reading only ever moves
                    // forward, so a made-up id would hide every later message
                    // from the unread count for good.
                    $readId = min($value, (int) $dialog->last_message_id);

                    if ($readId > 0) {
                        $dialog->afterSave(function (Dialog $dialog) use ($readId, $context) {
                            $this->bus->dispatch(
                                new ReadDialog($dialog->id, $context->getActor(), $readId)
                            );
                        });
                    }
                }),

            Schema\Relationship\ToMany::make('messages')
                ->type('dialog-messages'),
            // Every member: a group has at most RecipientLimit::CEILING others.
            Schema\Relationship\ToMany::make('users')
                ->description('Members of the dialog (at most five).')
                ->type('users')
                ->includable(),
            // Linked even when not included: the frontend reads these ids to
            // know where a conversation starts and ends.
            Schema\Relationship\ToOne::make('firstMessage')
                ->type('dialog-messages')
                ->withLinkage()
                ->includable(),
            Schema\Relationship\ToOne::make('lastMessage')
                ->type('dialog-messages')
                ->withLinkage()
                ->includable(),
            Schema\Relationship\ToOne::make('lastMessageUser')
                ->description('The author of the latest message.')
                ->type('users')
                ->includable(),
        ];
    }

    /**
     * The other members, up to three by name in alphabetical order, then how
     * many more: "bob, carol, dave +3".
     */
    protected function groupTitle(Dialog $dialog, User $actor): string
    {
        $names = $dialog->users
            ->reject(fn (User $user) => $user->is($actor))
            ->map(fn (User $user) => $user->display_name)
            ->sort(fn (string $a, string $b) => strnatcasecmp($a, $b))
            ->values();

        return $this->translator->trans('flarum-messages.lib.dialog.group_title', [
            'names' => $names->take(self::TITLE_NAMES)->implode(', '),
            'others' => max(0, $names->count() - self::TITLE_NAMES),
        ]);
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('createdAt')
                ->ascendingAlias('oldest')
                ->descendingAlias('newest'),
            SortColumn::make('lastMessageAt')
                ->descendingAlias('latest'),
        ];
    }
}
