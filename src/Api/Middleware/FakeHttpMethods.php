<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FakeHttpMethods implements MiddlewareInterface
{
    const HEADER_NAME = 'x-http-method-override';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() === 'POST' && $request->hasHeader(self::HEADER_NAME)) {
            $fakeMethod = $request->getHeaderLine(self::HEADER_NAME);

            $request = $request->withMethod(strtoupper($fakeMethod));
        }

        return $handler->handle($request);
    }
}
