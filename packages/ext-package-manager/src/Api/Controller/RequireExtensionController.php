<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Api\Controller;

use Flarum\Bus\Dispatcher;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Flarum\PackageManager\Api\Serializer\ExtensionSerializer;
use Flarum\PackageManager\Command\RequireExtension;
use Flarum\PackageManager\Extension\ExtensionUtils;
use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class RequireExtensionController implements RequestHandlerInterface
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
        $package = Arr::get($request->getParsedBody(), 'data.package');

        $data = $this->bus->dispatch(
            new RequireExtension($actor, $package)
        );

        return new JsonResponse($data);
    }
}
