<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Api\Controller;

use Flarum\Bus\Dispatcher;
use Flarum\Http\RequestUtil;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Flarum\PackageManager\Command\MajorUpdate;

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
