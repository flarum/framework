<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Command;

use Flarum\User\User;

class DeleteAllNotifications
{
    public function __construct(
        public User $actor
    ) {
    }
}
