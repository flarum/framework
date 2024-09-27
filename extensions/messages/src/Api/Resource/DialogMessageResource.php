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
use Flarum\Foundation\ErrorHandling\LogReporter;
use Flarum\Foundation\ValidationException;
use Flarum\Locale\Translator;
use Flarum\Messages\Command\ReadDialog;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Tobyz\JsonApiServer\Context as OriginalContext;

/**
 * @extends Resource\AbstractDatabaseResource<DialogMessage>
 */
class DialogMessageResource extends Resource\AbstractDatabaseResource
{
    public function __construct(
        protected Translator $translator,
        protected LogReporter $log,
        protected Dispatcher $bus,
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
            Endpoint\Index::make()
                ->authenticated()
                ->paginate(),
        ];
    }

    public function fields(): array
    {
        return [

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

            // Write-only.
            Schema\Arr::make('users')
                ->requiredOnCreateWithout(['relationships.dialog'])
                ->writableOnCreate()
                ->hidden()
                ->items(1)
                ->set(fn () => null),

            Schema\Relationship\ToOne::make('user')
                ->type('users')
                ->includable(),
            Schema\Relationship\ToOne::make('dialog')
                ->type('dialogs')
                ->includable()
                ->writableOnCreate()
                ->requiredOnCreateWithout(['attributes.users']),

        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('createdAt'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function creating(object $model, OriginalContext $context): ?object
    {
        $model->user_id = $context->getActor()->id;
        $data = $context->body()['data'] ?? [];

        $this->events->dispatch(
            new DialogMessage\Event\Creating($model, $data)
        );

        if (! $model->dialog_id) {
            $context->getActor()->assertCan('sendAnyMessage');

            $users = array_filter(Arr::pluck($data['attributes']['users'] ?? [], 'id'), fn (mixed $id) => $id && $id != $model->user_id);

            if (empty($users)) {
                throw new ValidationException([
                    'users' => str_replace(':attribute', 'users', $this->translator->trans('validation.required')),
                ]);
            }

            $dialog = Dialog::for($model, $users);

            $model->dialog()->associate($dialog);

            $users[] = $model->user_id;

            $dialog->users()->syncWithPivotValues(array_unique($users), [
                'joined_at' => Carbon::now(),
            ]);
        }

        return parent::creating($model, $context);
    }

    /**
     * @inheritDoc
     */
    public function created(object $model, OriginalContext $context): ?object
    {
        if ($model->dialog->last_message_id !== $model->id) {
            $model->dialog->setLastMessage($model);
        }

        if (! $model->dialog->first_message_id) {
            $model->dialog->setFirstMessage($model);
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
