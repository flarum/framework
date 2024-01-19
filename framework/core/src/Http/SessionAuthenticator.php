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
    public function logIn(Session $session, AccessToken $token): void
    {
        $session->regenerate(true);
        $session->put('access_token', $token->token);
    }

    public function logOut(Session $session): void
    {
        $token = AccessToken::findValid($session->get('access_token'));

        $token?->delete();

        $session->invalidate();
        $session->regenerateToken();
    }
}
