<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Push\Listener;

use Flarum\Notification\Event\Sent;
use Flarum\Realtime\Push\Jobs\SendNotificationsJob;
use Illuminate\Contracts\Queue\Queue;

/**
 * Queues the realtime broadcast for freshly-inserted notifications.
 *
 * Listening to {@see Sent} (dispatched after the records are inserted) rather than broadcasting
 * from the notification driver — which runs in parallel with the insert — guarantees the rows
 * exist by the time the broadcast job looks them up, so there is no race to drop or retry.
 */
class BroadcastNotifications
{
    public function __construct(
        protected Queue $queue
    ) {
    }

    public function handle(Sent $event): void
    {
        if (! count($event->recipients)) {
            return;
        }

        $this->queue->push(
            new SendNotificationsJob($event->blueprint, $event->recipients)
        );
    }
}
