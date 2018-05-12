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

use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\Event\ConfigureNotificationTypes;
use Flarum\Notification\NotificationSyncer;
use Flarum\Suspend\Event\Suspended;
use Flarum\Suspend\Event\Unsuspended;
use Flarum\Suspend\Notification\UserSuspendedBlueprint;
use Flarum\Suspend\Notification\UserUnsuspendedBlueprint;
use Illuminate\Contracts\Events\Dispatcher;

class SendNotificationWhenUserIsSuspended
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

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureNotificationTypes::class, [$this, 'registerNotificationType']);
        $events->listen(Suspended::class, [$this, 'whenSuspended']);
        $events->listen(Unsuspended::class, [$this, 'whenUnsuspended']);
    }

    /**
     * @param ConfigureNotificationTypes $event
     */
    public function registerNotificationType(ConfigureNotificationTypes $event)
    {
        $event->add(UserSuspendedBlueprint::class, BasicUserSerializer::class, ['alert', 'email']);
        $event->add(UserUnsuspendedBlueprint::class, BasicUserSerializer::class, ['alert', 'email']);
    }

    /**
     * @param Suspended $event
     */
    public function whenSuspended(Suspended $event)
    {
        $this->notifications->sync(
            new UserSuspendedBlueprint($event->user, $event->actor),
            [$event->user]
        );
    }

    /**
     * @param Unsuspended $event
     */
    public function whenUnsuspended(Unsuspended $event)
    {
        $this->notifications->sync(
            new UserUnsuspendedBlueprint($event->user, $event->actor),
            [$event->user]
        );
    }
}
