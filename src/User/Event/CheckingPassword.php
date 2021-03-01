<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Event;

use Flarum\User\User;

/**
 * @deprecated beta 16, remove in beta 17. Use Auth extender instead.
 */
class CheckingPassword
{
    /**
     * @var User
     */
    public $user;

    /**
     * @var string
     */
    public $password;

    /**
     * @param User $user
     * @param string $password
     */
    public function __construct($user, $password)
    {
        $this->user = $user;
        $this->password = $password;
    }
}
