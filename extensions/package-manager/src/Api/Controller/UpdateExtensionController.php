<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\ExtensionManager\Command\UpdateExtension;
use Flarum\ExtensionManager\Job\Dispatcher;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UpdateExtensionController implements RequestHandlerInterface
{
    /**
     * @var Dispatcher
     */
    protected $bus;

    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $extensionId = Arr::get($request->getQueryParams(), 'id');
        $updateMode = Arr::get($request->getParsedBody(), 'data.updateMode');

        $response = $this->bus->dispatch(
            new UpdateExtension($actor, $extensionId, $updateMode)
        );

        return $response->queueJobs
            ? new JsonResponse(['processing' => true], 202)
            : new EmptyResponse(201);
    }
}
