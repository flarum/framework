<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Api\Resource;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Gdpr\Jobs\ExportJob;
use Flarum\Gdpr\Jobs\GdprJob;
use Flarum\Gdpr\Models\Export;
use Flarum\User\User;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;

/**
 * @extends Resource\AbstractDatabaseResource<Export>
 */
class ExportResource extends Resource\AbstractDatabaseResource
{
    public function __construct(
        protected Queue $queue
    ) {
    }

    public function routeNamePrefix(): ?string
    {
        return 'gdpr';
    }

    public function type(): string
    {
        return 'gdpr-exports';
    }

    public function model(): string
    {
        return Export::class;
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Endpoint::make('export')
                ->route('POST', '/')
                ->authenticated()
                ->action(function (Context $context) {
                    $actor = $context->getActor();
                    $user = User::query()->where('id', Arr::get($context->body(), 'data.attributes.userId'))->firstOrFail();

                    $actor->assertCan('exportFor', $user);

                    $this->queue->push(
                        job: new ExportJob($user, $actor),
                        queue: GdprJob::$onQueue
                    );
                })
                ->response(fn () => new EmptyResponse(201)),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('file'),
            Schema\DateTime::make('createdAt'),
            Schema\DateTime::make('destroysAt'),

            Schema\Relationship\ToOne::make('user')
                ->includable()
                ->type('users'),
            Schema\Relationship\ToOne::make('actor')
                ->includable()
                ->type('users'),
        ];
    }
}
