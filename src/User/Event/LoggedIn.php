<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Event;

use Flarum\Http\AccessToken;
use Flarum\User\User;

class LoggedIn
{
    public $user;

    public $token;

    public function __construct(User $user, AccessToken $token)
    {
        $this->user = $user;
        $this->token = $token;
    }
}
