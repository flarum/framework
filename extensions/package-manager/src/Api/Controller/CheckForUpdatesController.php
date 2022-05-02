<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\PackageManager\Command\CheckForUpdates;
use Flarum\PackageManager\Job\Dispatcher;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CheckForUpdatesController implements RequestHandlerInterface
{
    /**
     * @var Dispatcher
     */
    protected $bus;

    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * @throws \Flarum\User\Exception\PermissionDeniedException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);

        $actor->assertAdmin();

        /**
         * @TODO somewhere, if we're queuing, check that a similar composer command isn't already running,
         * to avoid duplicate jobs.
         */
        $response = $this->bus->dispatch(
            new CheckForUpdates($actor)
        );

        return $response->queueJobs
            ? new JsonResponse(['processing' => true], 202)
            : new JsonResponse($response->data);
    }
}
