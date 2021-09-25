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
use SychO\PackageManager\Command\UpdateExtension;

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

    /**
     * @throws \Flarum\User\Exception\PermissionDeniedException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $extensionId = Arr::get($request->getQueryParams(), 'id');

        $this->bus->dispatch(
            new UpdateExtension($actor, $extensionId)
        );

        return new EmptyResponse();
    }
}
