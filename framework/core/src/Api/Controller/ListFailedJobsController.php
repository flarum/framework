<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\Queue\FailedJobs;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\SyncQueue;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ListFailedJobsController implements RequestHandlerInterface
{
    public function __construct(
        protected FailedJobs $failed,
        protected Queue $queue
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        if ($this->queue instanceof SyncQueue) {
            return new JsonResponse(['errors' => [['status' => '404', 'title' => 'Not Found']]], 404);
        }

        return new JsonResponse(['data' => $this->failed->all()]);
    }
}
