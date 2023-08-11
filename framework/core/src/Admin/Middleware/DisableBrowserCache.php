<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Admin\Middleware;

use Closure;
use Flarum\Http\Middleware\IlluminateMiddlewareInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DisableBrowserCache implements IlluminateMiddlewareInterface
{
    /**
     * @inheritDoc
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'max-age=0, no-store');

        return $response;
    }
}
