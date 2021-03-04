<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Illuminate\Contracts\Session\Session;

class SessionAuthenticator
{
    /**
     * @param Session $session
     * @param AccessToken|int $token Token or user ID. Use of User ID is deprecated in beta 16, will be removed in beta 17
     */
    public function logIn(Session $session, $token)
    {
        // Backwards compatibility with $userId as parameter
        // Remove in beta 17
        if (! ($token instanceof AccessToken)) {
            $userId = $token;

            trigger_error('Parameter $userId is deprecated in beta 16, will be replaced by $token in beta 17', E_USER_DEPRECATED);

            $token = SessionAccessToken::generate($userId);
        }

        $session->regenerate(true);
        $session->put('access_token', $token->token);
    }

    /**
     * @param Session $session
     */
    public function logOut(Session $session)
    {
        $token = AccessToken::findValid($session->get('access_token'));

        if ($token) {
            $token->delete();
        }

        $session->invalidate();
        $session->regenerateToken();
    }
}
