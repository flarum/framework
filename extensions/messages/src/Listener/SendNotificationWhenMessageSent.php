<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Listener;

use Flarum\Messages\DialogMessage;
use Flarum\Messages\Job;
use Illuminate\Contracts\Queue\Queue;

class SendNotificationWhenMessageSent
{
    public function __construct(
        protected Queue $queue
    ) {
    }

    public function handle(DialogMessage\Event\Created $event): void
    {
        $this->queue->push(new Job\SendMessageNotificationsJob($event->message));
    }
}
