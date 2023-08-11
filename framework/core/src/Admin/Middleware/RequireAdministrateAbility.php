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
use Flarum\Http\RequestUtil;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAdministrateAbility implements IlluminateMiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        RequestUtil::getActor($request)->assertAdmin();

        return $next($request);
    }
}
