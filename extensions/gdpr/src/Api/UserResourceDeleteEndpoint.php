<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Api;

use Carbon\Carbon;
use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractResource;
use Flarum\Foundation\ValidationException;
use Flarum\Gdpr\Jobs\ErasureJob;
use Flarum\Gdpr\Jobs\GdprJob;
use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Support\Arr;

class UserResourceDeleteEndpoint
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected Queue $queue
    ) {
    }

    public function __invoke(Endpoint\Delete $endpoint): Endpoint\Delete
    {
        return $endpoint->action(function (Context $context) use ($endpoint) {
            $model = $context->model;

            /** @var AbstractResource $resource */
            $resource = $context->resource($context->collection->resource($model, $context));

            $context = $context->withResource($resource);

            $endpoint->callBeforeHook($context);

            $this->deleteAction($model, $context);

            $endpoint->callAfterHook($context, $model);

            return null;
        });
    }

    /**
     * @throws ValidationException
     */
    protected function deleteAction(User $user, Context $context): void
    {
        $actor = $context->getActor();
        $mode = Arr::get($context->body(), 'gdprMode', $this->settings->get('flarum-gdpr.default-erasure'));

        if (! in_array($mode, [ErasureRequest::MODE_ANONYMIZATION, ErasureRequest::MODE_DELETION])) {
            throw new ValidationException(['mode' => "Invalid erasure mode: $mode"]);
        }

        ErasureRequest::unguard();

        $erasureRequest = ErasureRequest::firstOrNew([
            'user_id' => $user->id,
        ]);

        $erasureRequest->user_id = $user->id;
        $erasureRequest->status = ErasureRequest::STATUS_MANUAL;
        $erasureRequest->created_at = Carbon::now();
        $erasureRequest->processed_mode = $mode;
        $erasureRequest->processed_at = Carbon::now();
        $erasureRequest->processed_by = $actor->id;

        $erasureRequest->save();

        ErasureRequest::reguard();

        $this->queue->push(
            job: new ErasureJob($erasureRequest),
            queue: GdprJob::$onQueue
        );
    }
}
