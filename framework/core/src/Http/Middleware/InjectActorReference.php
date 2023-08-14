<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Closure;
use Flarum\Http\RequestUtil;
use Flarum\User\Guest;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectActorReference implements IlluminateMiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        if (isset($GLOBALS['testing'])) dump('i', $request);
        $request = RequestUtil::withActor($request, new Guest);

        return $next($request);
    }
}
