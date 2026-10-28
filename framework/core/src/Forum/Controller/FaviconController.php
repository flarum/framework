<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Forum\Branding\Branding;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * For crawlers and feed readers that ask for /favicon.ico without reading the
 * page. Browsers follow the page's own icon links instead.
 */
class FaviconController implements RequestHandlerInterface
{
    public function __construct(
        protected Branding $branding
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $url = $this->branding->faviconIcoUrl() ?? $this->branding->faviconUrl();

        if (! $url) {
            return new EmptyResponse(404);
        }

        return new RedirectResponse($url, 302, ['Cache-Control' => 'public, max-age=86400']);
    }
}
