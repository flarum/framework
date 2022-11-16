<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Listener;

use Flarum\Approval\Event\PostWasApproved;
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

    /**
     * @param Posted|PostWasApproved $event
     * @return void
     */
    public function handle($event)
    {
        $this->queue->push(
            new SendReplyNotification($event->post, $event->post->discussion->last_post_number)
        );
    }
}
