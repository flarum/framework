<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend\Listener;

use Flarum\Notification\NotificationSyncer;
use Flarum\Suspend\Event\Unsuspended;
use Flarum\Suspend\Notification\UserUnsuspendedBlueprint;

class SendNotificationWhenUserIsUnsuspended
{
    public function __construct(
        protected NotificationSyncer $notifications
    ) {
    }

    public function handle(Unsuspended $event): void
    {
        $this->notifications->sync(
            new UserUnsuspendedBlueprint($event->user),
            [$event->user]
        );
    }
}
