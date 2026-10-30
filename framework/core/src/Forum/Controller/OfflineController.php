<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Forum\ServiceWorker;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * The page the service worker stores, to show when a page can't be loaded.
 */
class OfflineController implements RequestHandlerInterface
{
    public function __construct(
        protected ServiceWorker $serviceWorker
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->serviceWorker->offlinePage(), 200, [
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
