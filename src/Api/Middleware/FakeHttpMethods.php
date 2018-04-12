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

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

class FakeHttpMethods implements MiddlewareInterface
{
    const HEADER_NAME = 'x-http-method-override';

    public function process(Request $request, DelegateInterface $delegate)
    {
        if ($request->getMethod() === 'POST' && $request->hasHeader(self::HEADER_NAME)) {
            $fakeMethod = $request->getHeaderLine(self::HEADER_NAME);

            $request = $request->withMethod(strtoupper($fakeMethod));
        }

        return $delegate->process($request);
    }
}
