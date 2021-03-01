<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\ErrorHandling;

use Franzl\Middleware\Whoops\WhoopsRunner;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Handle errors using the Whoops error handler for debugging.
 *
 * Proper status codes for all known error types are returned. In addition,
 * content negotiation is performed to return proper responses in various
 * environments such as HTML frontends or API backends.
 *
 * Should only be used in debug mode (because Whoops may expose sensitive data).
 */
class WhoopsFormatter implements HttpFormatter
{
    public function format(HandledError $error, Request $request): Response
    {
        return WhoopsRunner::handle($error->getException(), $request)
            ->withStatus($error->getStatusCode());
    }
}
