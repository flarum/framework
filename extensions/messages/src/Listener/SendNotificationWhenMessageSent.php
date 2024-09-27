<?php

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
