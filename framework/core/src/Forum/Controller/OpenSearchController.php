<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Forum\OpenSearch\OpenSearchDescription;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Served by Flarum at an address without an .xml extension, which some web
 * server configurations, including the nginx one Flarum suggests, would
 * otherwise only look for on disk.
 */
class OpenSearchController implements RequestHandlerInterface
{
    public function __construct(
        protected OpenSearchDescription $description
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Browsers read the description whatever its type, but they download
        // the OpenSearch one when the address is opened, rather than show it.
        return new TextResponse($this->description->build(), 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
