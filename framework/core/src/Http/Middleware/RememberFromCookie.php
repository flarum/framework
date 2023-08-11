<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Closure;
use Flarum\Http\AccessToken;
use Flarum\Http\CookieFactory;
use Flarum\Http\RememberAccessToken;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RememberFromCookie implements IlluminateMiddlewareInterface
{
    public function __construct(
        protected CookieFactory $cookie
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->cookie($this->cookie->getName('remember'));

        if ($id) {
            $token = AccessToken::findValid($id);

            if ($token && $token instanceof RememberAccessToken) {
                $token->touch(request: $request);

                /** @var \Illuminate\Contracts\Session\Session $session */
                $session = $request->attributes->get('session');
                $session->put('access_token', $token->token);
            }
        }

        return $next($request);
    }
}
