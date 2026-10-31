<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Forum\ServiceWorker;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ServiceWorkerPartController implements RequestHandlerInterface
{
    public function __construct(
        protected ServiceWorker $serviceWorker
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $script = $this->serviceWorker->part((string) Arr::get($request->getQueryParams(), 'part'));

        if ($script === null) {
            return new EmptyResponse(404);
        }

        return new TextResponse($script, 200, [
            'Content-Type' => 'text/javascript; charset=utf-8',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
