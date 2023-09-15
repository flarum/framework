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
use Flarum\Http\RequestUtil;
use Flarum\User\Guest;
use Flarum\User\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithSession implements IlluminateMiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->attributes->get('session');

        $actor = $this->getActor($session, $request);

        $request = RequestUtil::withActor($request, $actor);

        return $next($request);
    }

    private function getActor(Session $session, Request $request): Guest|User
    {
        if ($session->has('access_token')) {
            $token = AccessToken::findValid($session->get('access_token'));

            if ($token) {
                $actor = $token->user;
                $actor->updateLastSeen()->save();

                $token->touch(request: $request);

                return $actor;
            }

            // If this session used to have a token which is no longer valid we properly refresh the session
            $session->invalidate();
            $session->regenerateToken();
        }

        return new Guest;
    }
}
