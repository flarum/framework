<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\ExtensionManager\Command\WhyNot;
use Flarum\ExtensionManager\Job\Dispatcher;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class WhyNotController implements RequestHandlerInterface
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
        $package = Arr::get($request->getParsedBody(), 'data.package', '');
        $version = Arr::get($request->getParsedBody(), 'data.version', '*');

        $whyNot = $this->bus->sync()->dispatch(
            new WhyNot($actor, $package, $version)
        );

        return new JsonResponse(['data' => ['reason' => $whyNot->data['reason']]]);
    }
}
