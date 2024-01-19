<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Api\Controller;

use Flarum\ExtensionManager\Command\CheckForUpdates;
use Flarum\ExtensionManager\Job\Dispatcher;
use Flarum\Http\RequestUtil;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CheckForUpdatesController implements RequestHandlerInterface
{
    public function __construct(
        protected Dispatcher $bus
    ) {
    }

    /**
     * @throws \Flarum\User\Exception\PermissionDeniedException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);

        $actor->assertAdmin();

        $response = $this->bus->dispatch(
            new CheckForUpdates($actor)
        );

        return $response->queueJobs
            ? new JsonResponse(['processing' => true], 202)
            : new JsonResponse($response->data);
    }
}
