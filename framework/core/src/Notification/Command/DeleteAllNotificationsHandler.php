<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Command;

use Flarum\Notification\Event\DeletedAll;
use Flarum\Notification\NotificationRepository;
use Flarum\User\Exception\NotAuthenticatedException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;

class DeleteAllNotificationsHandler
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
     * @param Dispatcher $events
     */
    public function __construct(NotificationRepository $notifications, Dispatcher $events)
    {
        $this->notifications = $notifications;
        $this->events = $events;
    }

    /**
     * @param DeleteAllNotifications $command
     * @throws NotAuthenticatedException
     */
    public function handle(DeleteAllNotifications $command)
    {
        $actor = $command->actor;

        $actor->assertRegistered();

        $this->notifications->deleteAll($actor);

        $this->events->dispatch(new DeletedAll($actor, Carbon::now()));
    }
}
