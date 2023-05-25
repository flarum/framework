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
use Illuminate\Support\Carbon;

class ReadAllNotificationsHandler
{
    public function __construct(
        protected NotificationRepository $notifications,
        protected Dispatcher $events
    ) {
    }

    /**
     * @throws \Flarum\User\Exception\NotAuthenticatedException
     */
    public function handle(ReadAllNotifications $command): void
    {
        $actor = $command->actor;

        $actor->assertRegistered();

        $this->notifications->markAllAsRead($actor);

        $this->events->dispatch(new ReadAll($actor, Carbon::now()));
    }
}
