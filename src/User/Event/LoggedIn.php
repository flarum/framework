<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\User\Event;

use Flarum\User\User;
use Flarum\Http\AccessToken;

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
