<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Dialog\Event;

use Flarum\Messages\Dialog;
use Flarum\User\User;

/**
 * A member has left a group. If they were the last, the dialog is gone too.
 */
class UserLeft
{
    public function __construct(
        public Dialog $dialog,
        public User $user
    ) {
    }
}
