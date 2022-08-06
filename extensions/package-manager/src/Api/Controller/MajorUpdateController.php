<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\PackageManager\Command\MajorUpdate;
use Flarum\PackageManager\Job\Dispatcher;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MajorUpdateController implements RequestHandlerInterface
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
        $dryRun = (bool) (int) Arr::get($request->getParsedBody(), 'data.dryRun', 0);

        $response = $this->bus->dispatch(
            new MajorUpdate($actor, $dryRun)
        );

        return $response->queueJobs
            ? new JsonResponse(['processing' => true], 202)
            : new EmptyResponse(201);
    }
}
