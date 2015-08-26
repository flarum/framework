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

class UserPasswordWasChanged
{
    /**
     * The user whose password was changed.
     *
     * @var User
     */
    public $user;

    /**
     * @param User $user The user whose password was changed.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
