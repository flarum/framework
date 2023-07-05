<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend\Event;

use Flarum\User\User;

class Suspended
{
    public function __construct(
        public User $user,
        public User $actor
    ) {
    }
}
