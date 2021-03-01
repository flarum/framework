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
     * @param int $userId
     */
    public function logIn(Session $session, $userId)
    {
        $session->regenerate(true);
        $session->put('user_id', $userId);
    }

    /**
     * @param Session $session
     */
    public function logOut(Session $session)
    {
        $session->invalidate();
        $session->regenerateToken();
    }
}
