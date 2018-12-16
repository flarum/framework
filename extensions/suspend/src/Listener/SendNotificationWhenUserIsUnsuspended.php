<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Suspend\Listener;

use Flarum\Notification\NotificationSyncer;
use Flarum\Suspend\Event\Unsuspended;
use Flarum\Suspend\Notification\UserUnsuspendedBlueprint;

class SendNotificationWhenUserIsUnsuspended
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

    public function handle(Unsuspended $event)
    {
        $this->notifications->sync(
            new UserUnsuspendedBlueprint($event->user, $event->actor),
            [$event->user]
        );
    }
}
