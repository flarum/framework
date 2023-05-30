<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Listener;

use Flarum\Notification\NotificationSyncer;
use Flarum\Post\Event\Restored;
use Flarum\Subscriptions\Notification\NewPostBlueprint;

class RestoreNotificationWhenPostIsRestored
{
    public function __construct(
        protected NotificationSyncer $notifications
    ) {
    }

    public function handle(Restored $event): void
    {
        $this->notifications->restore(new NewPostBlueprint($event->post));
    }
}
