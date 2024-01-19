<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Event;

use DateTime;
use Flarum\Notification\Notification;
use Flarum\User\User;

class Read
{
    public function __construct(
        public User $actor,
        public Notification $notification,
        public DateTime $timestamp
    ) {
    }
}
