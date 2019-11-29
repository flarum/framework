<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Listener;

use Flarum\Notification\NotificationSyncer;
use Flarum\Post\Event\Posted;
use Flarum\Subscriptions\Notification\NewPostBlueprint;

class SendNotificationWhenReplyIsPosted
{
    /**
     * @var NotificationSyncer
     */
    protected $notifications;

    /**
     * @param NotificationSyncer $notifications
     */
    public function __construct(NotificationSyncer $notifications)
    {
        $this->notifications = $notifications;
    }

    public function handle(Posted $event)
    {
        $post = $event->post;
        $discussion = $post->discussion;

        $notify = $discussion->readers()
            ->where('users.id', '!=', $post->user_id)
            ->where('discussion_user.subscription', 'follow')
            ->where('discussion_user.last_read_post_number', $discussion->last_post_number)
            ->get();

        $this->notifications->sync(
            new NewPostBlueprint($event->post),
            $notify->all()
        );
    }
}
