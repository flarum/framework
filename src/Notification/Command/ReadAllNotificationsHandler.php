<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Command;

use Flarum\Notification\Event\ReadAll;
use Flarum\Notification\NotificationRepository;
use Illuminate\Contracts\Events\Dispatcher;

class ReadAllNotificationsHandler
{
    /**
     * @var NotificationRepository
     */
    protected $notifications;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @param NotificationRepository $notifications
     */
    public function __construct(NotificationRepository $notifications, Dispatcher $events)
    {
        $this->notifications = $notifications;
        $this->events = $events;
    }

    /**
     * @param ReadAllNotifications $command
     * @throws \Flarum\User\Exception\PermissionDeniedException
     */
    public function handle(ReadAllNotifications $command)
    {
        $actor = $command->actor;

        $actor->assertRegistered();

        $this->notifications->markAllAsRead($actor);

        $this->events->dispatch(new ReadAll($actor));
    }
}
