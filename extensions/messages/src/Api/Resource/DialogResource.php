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
use Flarum\Messages\Command\ReadDialog;
use Flarum\Messages\Dialog;
use Flarum\Messages\UserDialogState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laminas\Diactoros\Response\EmptyResponse;
use Tobyz\JsonApiServer\Context as OriginalContext;

/**
 * @extends Resource\AbstractDatabaseResource<Dialog>
 */
class DialogResource extends Resource\AbstractDatabaseResource
{
    public function __construct(
        protected Translator $translator,
        protected Dispatcher $bus,
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
                ->eagerLoad('state'),
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
            Endpoint\Index::make()
                ->authenticated()
                ->defaultSort('-lastMessageAt')
                ->paginate()
                ->eagerLoad(['users', 'state']),
        ];
    }

    public function fields(): array
    {
        return [

            Schema\Str::make('title')
                ->get(function (Dialog $dialog, Context $context) {
                    // The other member may have left the forum since.
                    return $this->translator->trans('flarum-messages.lib.dialog.title', [
                        '{username}' => $dialog->recipient($context->getActor())->display_name
                            ?? $this->translator->trans('core.lib.username.deleted_text'),
                    ]);
                }),
            Schema\Str::make('type')
                ->minLength(3)
                ->maxLength(255)
                ->in(Dialog::$types),
            Schema\DateTime::make('lastMessageAt'),
            Schema\DateTime::make('createdAt'),

            Schema\Integer::make('unreadCount')
                ->countRelation('messages', function (Builder $query, Context $context) {
                    $query->leftJoin('dialog_user', 'dialog_messages.dialog_id', '=', 'dialog_user.dialog_id')
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
            Schema\Integer::make('lastReadMessageId')
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
            Schema\Relationship\ToMany::make('users')
                ->type('users')
                ->scope(fn (BelongsToMany $query) => $query->limit(5))
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
                ->type('users')
                ->includable(),
        ];
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
