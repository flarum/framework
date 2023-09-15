<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\ErrorHandling;

use Flarum\Http\RequestUtil;
use Franzl\Middleware\Whoops\WhoopsRunner;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
        $psr7Request = RequestUtil::toPsr7($request);

        $psr7Response = WhoopsRunner::handle($error->getException(), $psr7Request)
            ->withStatus($error->getStatusCode());

        return RequestUtil::responseToSymfony($psr7Response);
    }
}
