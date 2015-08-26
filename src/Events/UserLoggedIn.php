<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Events;

use Flarum\Core\Users\User;

class UserLoggedIn
{
    public $user;

    public $token;

    public function __construct(User $user, $token)
    {
        $this->user = $user;
        $this->token = $token;
    }
}
