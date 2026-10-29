<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\Queue\QueueStatsProvider;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\SyncQueue;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ShowQueueStatsController implements RequestHandlerInterface
{
    public function __construct(
        protected QueueStatsProvider $stats,
        protected Queue $queue
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        // The sync driver runs jobs inline; there is no queue to inspect.
        if ($this->queue instanceof SyncQueue) {
            return new JsonResponse(['errors' => [['status' => '404', 'title' => 'Not Found']]], 404);
        }

        return new JsonResponse([
            'totals' => $this->stats->totals(),
            'queues' => $this->stats->queues(),
        ]);
    }
}
