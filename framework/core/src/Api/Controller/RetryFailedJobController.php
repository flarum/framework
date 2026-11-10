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
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RetryFailedJobController implements RequestHandlerInterface
{
    public function __construct(
        protected FailedJobs $failed
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $id = Arr::get($request->getAttribute('routeParameters'), 'id');

        if (! $id) {
            $this->failed->retryAll();
        } elseif (! $this->failed->retry($id)) {
            return new JsonResponse(['errors' => [['status' => '404', 'title' => 'Not Found']]], 404);
        }

        return new EmptyResponse(204);
    }
}
