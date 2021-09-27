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
use Psr\Http\Message\ServerRequestInterface;
use SychO\PackageManager\Command\MinorFlarumUpdate;

class MinorFlarumUpdateController implements RequestHandlerInterface
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

        $this->bus->dispatch(
            new MinorFlarumUpdate($actor)
        );

        return new EmptyResponse();
    }
}
