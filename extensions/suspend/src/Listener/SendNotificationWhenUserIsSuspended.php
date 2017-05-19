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

use DateTime;
use Flarum\Api\Serializer\UserBasicSerializer;
use Flarum\Core\Notification\NotificationSyncer;
use Flarum\Core\User;
use Flarum\Event\ConfigureNotificationTypes;
use Flarum\Suspend\Event\UserWasSuspended;
use Flarum\Suspend\Event\UserWasUnsuspended;
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
        $events->listen(UserWasSuspended::class, [$this, 'whenUserWasSuspended']);
        $events->listen(UserWasUnsuspended::class, [$this, 'whenUserWasUnsuspended']);
    }

    /**
     * @param ConfigureNotificationTypes $event
     */
    public function registerNotificationType(ConfigureNotificationTypes $event)
    {
        $event->add(UserSuspendedBlueprint::class, UserBasicSerializer::class, ['alert', 'email']);
        $event->add(UserUnsuspendedBlueprint::class, UserBasicSerializer::class, ['alert', 'email']);
    }

    /**
     * @param UserWasSuspended $event
     */
    public function whenUserWasSuspended(UserWasSuspended $event)
    {
        $this->sync($event->user, $event->actor, [$event->user]);
    }

    /**
     * @param UserWasSuspended $event
     */
    public function whenUserWasUnsuspended(UserWasUnsuspended $event)
    {
        $this->sync($event->user, $event->actor, [$event->user]);
    }

    /**
     * @param User $user
     * @param User $actor
     * @param array $recipients
     */
    public function sync(User $user, User $actor, array $recipients)
    {
        if ($user->suspend_until > new DateTime()) {
            $this->notifications->sync(
                new UserSuspendedBlueprint($user, $actor),
                $recipients
            );
        } else {
            $this->notifications->sync(
                new UserUnsuspendedBlueprint($user, $actor),
                $recipients
            );
        }
    }
}
