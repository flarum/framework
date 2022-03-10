<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Command;

use Carbon\Carbon;
use Flarum\Notification\Event\Read;
use Flarum\Notification\Notification;
use Illuminate\Contracts\Events\Dispatcher;

class ReadNotificationHandler
{
    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @param Dispatcher $events
     */
    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * @param ReadNotification $command
     * @return \Flarum\Notification\Notification
     * @throws \Flarum\User\Exception\PermissionDeniedException
     */
    public function handle(ReadNotification $command)
    {
        $actor = $command->actor;

        $actor->assertRegistered();

        $notification = Notification::where('user_id', $actor->id)->findOrFail($command->notificationId);

        Notification::where([
            'user_id' => $actor->id,
            'type' => $notification->type,
            'subject_id' => $notification->subject_id
        ])
            ->update(['read_at' => Carbon::now()]);

        $notification->read_at = Carbon::now();

        $this->events->dispatch(new Read($actor, $notification, Carbon::now()));

        return $notification;
    }
}
