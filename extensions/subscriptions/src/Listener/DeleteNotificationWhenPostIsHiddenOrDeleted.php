<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Listener;

use Flarum\Notification\NotificationSyncer;
use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\Hidden;
use Flarum\Subscriptions\Notification\NewPostBlueprint;

class DeleteNotificationWhenPostIsHiddenOrDeleted
{
    public function __construct(
        protected NotificationSyncer $notifications
    ) {
    }

    public function handle(Deleted|Hidden $event): void
    {
        $this->notifications->delete(new NewPostBlueprint($event->post));
    }
}
