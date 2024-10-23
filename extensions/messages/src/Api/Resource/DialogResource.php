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
use Illuminate\Support\Arr;
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

                    $table = $grammar->wrapTable('dialogs');
                    $column = $grammar->wrap('last_message_id');

                    UserDialogState::query()
                        ->where('dialog_user.user_id', $context->getActor()->id)
                        ->update([
                            'last_read_message_id' => $connection->raw('('.$grammar->compileSelect(
                                Dialog::query()
                                    ->select('last_message_id')
                                    ->from('dialogs')
                                    ->whereColumn('dialogs.id', 'dialog_user.dialog_id')
                                    ->toBase()
                            ).')'),
                            'last_read_at' => Carbon::now(),
                        ]);
                })
                ->response(fn () => new EmptyResponse(204)),
            Endpoint\Index::make()
                ->authenticated()
                ->paginate()
                ->eagerLoad(['users', 'state']),
        ];
    }

    public function fields(): array
    {
        return [

            Schema\Str::make('title')
                ->get(function (Dialog $dialog, Context $context) {
                    return $this->translator->trans('flarum-messages.lib.dialog.title', [
                        '{username}' => $dialog->recipient($context->getActor())->display_name,
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
            Schema\Integer::make('lastReadMessageId')
                ->visible(fn (Dialog $dialog) => $dialog->state !== null)
                ->get(function (Dialog $dialog) {
                    return $dialog->state?->last_read_message_id;
                })
                ->writableOnUpdate()
                ->set(function (Dialog $dialog, int $value, Context $context) {
                    if ($readNumber = Arr::get($context->body(), 'data.attributes.lastReadMessageId')) {
                        $dialog->afterSave(function (Dialog $dialog) use ($readNumber, $context) {
                            $this->bus->dispatch(
                                new ReadDialog($dialog->id, $context->getActor(), $readNumber)
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
            Schema\Relationship\ToOne::make('firstMessage')
                ->type('dialog-messages')
                ->includable(),
            Schema\Relationship\ToOne::make('lastMessage')
                ->type('dialog-messages')
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
