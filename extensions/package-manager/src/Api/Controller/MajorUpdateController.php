<?php

/**
 *
 */

namespace SychO\PackageManager\Api\Controller;

use Flarum\Bus\Dispatcher;
use Flarum\Http\RequestUtil;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use SychO\PackageManager\Command\MajorUpdate;

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
        $dryRun = (bool) (int) Arr::get($request->getParsedBody(), 'data.dryRun');

        $this->bus->dispatch(
            new MajorUpdate($actor, $dryRun)
        );

        return new EmptyResponse();
    }
}
