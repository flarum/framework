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
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;

class DeleteAllNotificationsHandler
{
    public function __construct(
        protected NotificationRepository $notifications,
        protected Dispatcher $events
    ) {
    }

    public function handle(DeleteAllNotifications $command): void
    {
        $actor = $command->actor;

        $actor->assertRegistered();

        $this->notifications->deleteAll($actor);

        $this->events->dispatch(new DeletedAll($actor, Carbon::now()));
    }
}
