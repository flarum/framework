<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Forum\ServiceWorker;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Served at the forum's root, as a browser lets a service worker look after
 * only the addresses under its own.
 */
class ServiceWorkerController implements RequestHandlerInterface
{
    public function __construct(
        protected ServiceWorker $serviceWorker
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new TextResponse($this->serviceWorker->script(), 200, [
            'Content-Type' => 'text/javascript; charset=utf-8',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
