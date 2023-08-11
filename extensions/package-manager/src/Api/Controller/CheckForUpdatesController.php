<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Api\Controller;

use Flarum\Http\Controller\AbstractController;
use Flarum\Http\RequestUtil;
use Flarum\PackageManager\Command\CheckForUpdates;
use Flarum\PackageManager\Job\Dispatcher;
use Illuminate\Http\Request;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;

class CheckForUpdatesController extends AbstractController
{
    public function __construct(
        protected Dispatcher $bus
    ) {
    }

    public function __invoke(Request $request): ResponseInterface
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
