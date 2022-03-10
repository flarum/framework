<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Listener;

use Flarum\Post\Event\Posted;
use Flarum\Subscriptions\Job\SendReplyNotification;
use Illuminate\Contracts\Queue\Queue;

class SendNotificationWhenReplyIsPosted
{
    /**
     * @var Queue
     */
    protected $queue;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    public function handle(Posted $event)
    {
        $this->queue->push(
            new SendReplyNotification($event->post, $event->post->discussion->last_post_number)
        );
    }
}
