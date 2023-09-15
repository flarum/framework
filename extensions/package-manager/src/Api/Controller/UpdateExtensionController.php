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
use Flarum\PackageManager\Command\UpdateExtension;
use Flarum\PackageManager\Job\Dispatcher;
use Illuminate\Http\Request;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;

class UpdateExtensionController extends AbstractController
{
    public function __construct(
        protected Dispatcher $bus
    ) {
    }

    public function __invoke(Request $request, int $id): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);

        $response = $this->bus->dispatch(
            new UpdateExtension($actor, $id)
        );

        return $response->queueJobs
            ? new JsonResponse(['processing' => true], 202)
            : new EmptyResponse(201);
    }
}
