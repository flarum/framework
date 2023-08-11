<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Middleware;

use Closure;
use Flarum\Http\Middleware\IlluminateMiddlewareInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FakeHttpMethods implements IlluminateMiddlewareInterface
{
    const HEADER_NAME = 'x-http-method-override';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getMethod() === 'POST' && $request->hasHeader(self::HEADER_NAME)) {
            $fakeMethod = $request->header(self::HEADER_NAME);

            $request->setMethod(strtoupper($fakeMethod));
        }

        return $next($request);
    }
}
