<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class FakeHttpMethods implements Middleware
{
    const HEADER_NAME = 'x-http-method-override';

    public function process(Request $request, Handler $handler): Response
    {
        if ($request->getMethod() === 'POST' && $request->hasHeader(self::HEADER_NAME)) {
            $fakeMethod = $request->getHeaderLine(self::HEADER_NAME);

            $request = $request->withMethod(strtoupper($fakeMethod));
        }

        return $handler->handle($request);
    }
}
