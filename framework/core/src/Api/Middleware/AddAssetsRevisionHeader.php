<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Middleware;

use Flarum\Frontend\Compiler\AssetsRevision;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

/**
 * Stamps every API response with the current asset revision token, so a browsing
 * client can notice when the JS/CSS it booted with has been superseded and offer
 * the user a reload — without polling or a forced refresh.
 */
class AddAssetsRevisionHeader implements Middleware
{
    public const HEADER_NAME = 'X-Flarum-Assets-Revision';

    public function __construct(
        protected AssetsRevision $revision
    ) {
    }

    public function process(Request $request, Handler $handler): Response
    {
        return $handler->handle($request)->withHeader(self::HEADER_NAME, $this->revision->token());
    }
}
