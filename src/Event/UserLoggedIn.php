<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Event;

use Flarum\Core\User;
use Flarum\Http\Session;

class UserLoggedIn
{
    public $user;

    public $session;

    public function __construct(User $user, Session $session)
    {
        $this->user = $user;
        $this->session = $session;
    }
}
