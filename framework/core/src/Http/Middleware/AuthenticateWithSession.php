<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Flarum\Http\AccessToken;
use Flarum\Http\RequestUtil;
use Flarum\User\Guest;
use Flarum\User\User;
use Illuminate\Contracts\Session\Session;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class AuthenticateWithSession implements Middleware
{
    public function process(Request $request, Handler $handler): Response
    {
        $session = $request->getAttribute('session');

        $actor = $this->getActor($session, $request);

        $request = RequestUtil::withActor($request, $actor);

        return $handler->handle($request);
    }

    private function getActor(Session $session, Request $request): Guest|User
    {
        if ($session->has('access_token')) {
            $token = AccessToken::findValid($session->get('access_token'));

            if ($token && ! $this->acceptsToken($token, $request)) {
                // Deleted, not just dropped from the session: a remember cookie
                // would otherwise sign the session straight back in.
                $token->delete();
                $token = null;
            }

            if ($token) {
                $actor = $token->user;
                $actor->updateLastSeen();

                // Only save if last_seen_at was actually updated (throttled to 180 seconds)
                if ($actor->isDirty()) {
                    $actor->save();
                }

                $token->touch(request: $request);

                return $actor;
            }

            // If this session used to have a token which is no longer valid we properly refresh the session
            $session->invalidate();
            $session->regenerateToken();
        }

        return new Guest;
    }

    /**
     * Whether a valid token may still authenticate this request.
     *
     * Override to check the session against an external authority, such as an
     * identity provider that may have revoked it upstream. Runs before the user's
     * activity or the token is touched; rejecting deletes the token and resets the
     * session, and the request continues as a guest.
     *
     * Session authentication only: a token sent as `Authorization: Token` is
     * checked by {@see AuthenticateWithHeader}, which does not call this.
     */
    protected function acceptsToken(AccessToken $token, Request $request): bool
    {
        return true;
    }
}
