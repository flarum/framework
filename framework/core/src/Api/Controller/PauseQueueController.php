<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\Queue\QueueFactory;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\SyncQueue;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PauseQueueController implements RequestHandlerInterface
{
    public function __construct(
        protected QueueFactory $factory,
        protected Queue $queue
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        // Sync jobs execute inline and never pass through the worker, so
        // there is nothing to pause.
        if ($this->queue instanceof SyncQueue) {
            return new EmptyResponse(409);
        }

        $body = $request->getParsedBody();
        $paused = (bool) Arr::get($body, 'paused', true);
        $queue = (string) Arr::get($body, 'queue', 'default');

        $connection = $this->queue->getConnectionName();

        if ($paused) {
            $this->factory->pause($connection, $queue);
        } else {
            $this->factory->resume($connection, $queue);
        }

        return new EmptyResponse(204);
    }
}
