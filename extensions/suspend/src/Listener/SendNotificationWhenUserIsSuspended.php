<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend\Listener;

use Flarum\Notification\NotificationSyncer;
use Flarum\Suspend\Event\Suspended;
use Flarum\Suspend\Notification\UserSuspendedBlueprint;

class SendNotificationWhenUserIsSuspended
{
    public function __construct(
        protected NotificationSyncer $notifications
    ) {
    }

    public function handle(Suspended $event): void
    {
        $this->notifications->sync(
            new UserSuspendedBlueprint($event->user),
            [$event->user]
        );
    }
}
