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
    public function __construct(
        protected Dispatcher $events
    ) {
    }

    /**
     * @throws \Flarum\User\Exception\NotAuthenticatedException
     */
    public function handle(ReadNotification $command): Notification
    {
        $actor = $command->actor;

        $actor->assertRegistered();

        /** @var Notification $notification */
        $notification = Notification::query()
            ->where('user_id', $actor->id)
            ->findOrFail($command->notificationId);

        Notification::query()->where([
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
