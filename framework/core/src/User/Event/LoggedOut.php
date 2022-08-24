<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Event;

use Flarum\User\User;

class LoggedOut
{
    /**
     * @var User
     */
    public $user;

    /**
     * @var bool
     */
    public $isGlobal;

    public function __construct(User $user, bool $isGlobal = false)
    {
        $this->user = $user;
        $this->isGlobal = $isGlobal;
    }
}
