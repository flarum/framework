<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Api\Resource;

use Carbon\Carbon;
use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Foundation\ValidationException;
use Flarum\Gdpr\Jobs\ErasureJob;
use Flarum\Gdpr\Jobs\GdprJob;
use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\Gdpr\Notifications\ConfirmErasureBlueprint;
use Flarum\Gdpr\Notifications\ErasureRequestCancelledBlueprint;
use Flarum\Notification\NotificationSyncer;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laminas\Diactoros\Response\EmptyResponse;
use Tobyz\JsonApiServer\Context as OriginalContext;

/**
 * @extends Resource\AbstractDatabaseResource<ErasureRequest>
 */
class ErasureRequestResource extends Resource\AbstractDatabaseResource
{
    public function __construct(
        protected NotificationSyncer $notifications,
        protected SettingsRepositoryInterface $settings,
        protected Queue $queue,
    ) {
    }

    public function routeNamePrefix(): ?string
    {
        return 'gdpr';
    }

    public function type(): string
    {
        return 'user-erasure-requests';
    }

    public function model(): string
    {
        return ErasureRequest::class;
    }

    public function scope(Builder $query, OriginalContext $context): void
    {
        $query->whereVisibleTo($context->getActor());
    }

    public function query(OriginalContext $context): object
    {
        if ($context->listing(self::class)) {
            return parent::query($context)->where('status', ErasureRequest::STATUS_USER_CONFIRMED);
        }

        return parent::query($context);
    }

    public function newModel(OriginalContext $context): object
    {
        if ($context->creating(self::class)) {
            return ErasureRequest::query()->firstOrNew([
                'user_id' => $context->getActor()->id,
            ]);
        }

        return parent::newModel($context);
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Create::make()
                ->authenticated()
                ->before(function (Context $context) {
                    $actor = $context->getActor();

                    // If they signed up using a third party oauth provider, they won't have a password
                    // so we can't check it. We'll just assume they're authenticated.
                    if ($actor->loginProviders()->count() === 0 && ! $actor->checkPassword(Arr::get($context->body(), 'meta.password', ''))) {
                        throw new ValidationException(['password' => 'Incorrect password']);
                    }
                }),
            Endpoint\Update::make()
                ->can('process'),
            Endpoint\Endpoint::make('cancel')
                ->route('POST', '{id}/cancel')
                ->authenticated()
                ->can('cancel')
                ->action(function (Context $context) {
                    /** @var ErasureRequest $request */
                    $request = $context->model;

                    $request->cancelled_at = Carbon::now();
                    $request->status = ErasureRequest::STATUS_CANCELLED;
                    $request->verification_token = null;
                    $request->save();

                    $this->notifications->sync(new ErasureRequestCancelledBlueprint($request), [$request->user]);
                })
                ->response(fn () => new EmptyResponse(204)),
            Endpoint\Index::make()
                ->can('processErasure')
                ->defaultInclude(['user'])
                ->paginate(),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('status'),
            Schema\Str::make('reason')
                ->nullable()
                ->writableOnCreate(),
            Schema\DateTime::make('createdAt'),
            Schema\DateTime::make('userConfirmedAt'),
            Schema\DateTime::make('processedAt'),
            Schema\Str::make('processedMode')
                ->writableOnUpdate()
                ->requiredOnUpdate()
                ->in(array_keys(array_filter([
                    ErasureRequest::MODE_ANONYMIZATION => $this->settings->get('flarum-gdpr.allow-anonymization'),
                    ErasureRequest::MODE_DELETION => $this->settings->get('flarum-gdpr.allow-deletion'),
                ]))),
            Schema\Str::make('processorComment')
                ->writableOnUpdate()
                ->maxLength(65535)
                ->nullable(),

            Schema\Relationship\ToOne::make('user')
                ->includable()
                ->inverse('erasureRequest')
                ->type('users'),
            Schema\Relationship\ToOne::make('processedBy')
                ->includable()
                ->type('users'),
        ];
    }

    public function sorts(): array
    {
        return [
            // SortColumn::make('createdAt'),
        ];
    }

    public function creating(object $model, OriginalContext $context): ?object
    {
        $model->user_id = $context->getActor()->id;
        $model->status = ErasureRequest::STATUS_AWAITING_USER_CONFIRMATION;
        $model->verification_token = Str::random(40);
        $model->created_at = Carbon::now();
        $model->cancelled_at = null;

        return $model;
    }

    public function created(object $model, OriginalContext $context): ?object
    {
        $this->notifications->sync(new ConfirmErasureBlueprint($model), [$context->getActor()]);

        return $model;
    }

    public function updating(object $model, OriginalContext $context): ?object
    {
        $model->status = ErasureRequest::STATUS_PROCESSED;
        $model->processed_at = Carbon::now();
        $model->processed_by = $context->getActor()->id;

        return $model;
    }

    public function updated(object $model, OriginalContext $context): ?object
    {
        $this->queue->push(
            job: new ErasureJob($model),
            queue: GdprJob::$onQueue
        );

        return $model;
    }
}
