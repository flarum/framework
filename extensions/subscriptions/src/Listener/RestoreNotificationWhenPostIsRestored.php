<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Listener;

use Flarum\Notification\NotificationSyncer;
use Flarum\Post\Event\Restored;
use Flarum\Subscriptions\Notification\NewPostBlueprint;

class RestoreNotificationWhenPostIsRestored
{
    /**
     * @var NotificationSyncer
     */
    protected $notifications;

    /**
     * @param NotificationSyncer $notifications
     */
    public function __construct(NotificationSyncer $notifications)
    {
        $this->notifications = $notifications;
    }

    public function handle(Restored $event)
    {
        $this->notifications->restore(new NewPostBlueprint($event->post));
    }
}
