<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Forum\Branding\WebManifest;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Served by Flarum rather than as a file, so it works on any web server and
 * stays on the forum's own domain when assets are served from elsewhere.
 */
class WebManifestController implements RequestHandlerInterface
{
    public function __construct(
        protected WebManifest $manifest
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse($this->manifest->build(), 200, [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'public, max-age=3600',
        ], JsonResponse::DEFAULT_JSON_FLAGS | JSON_UNESCAPED_SLASHES);
    }
}
