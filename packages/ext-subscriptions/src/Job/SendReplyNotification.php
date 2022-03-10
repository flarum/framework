<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Job;

use Flarum\Notification\NotificationSyncer;
use Flarum\Post\Post;
use Flarum\Subscriptions\Notification\NewPostBlueprint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class SendReplyNotification implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * @var Post
     */
    protected $post;

    /**
     * @var int
     */
    protected $lastPostNumber;

    /**
     * @param Post $post
     * @param int|null $lastPostNumber
     */
    public function __construct(Post $post, $lastPostNumber)
    {
        $this->post = $post;
        $this->lastPostNumber = $lastPostNumber;
    }

    public function handle(NotificationSyncer $notifications)
    {
        $post = $this->post;
        $discussion = $post->discussion;

        $notify = $discussion->readers()
            ->where('users.id', '!=', $post->user_id)
            ->where('discussion_user.subscription', 'follow')
            ->where('discussion_user.last_read_post_number', $this->lastPostNumber)
            ->get();

        $notifications->sync(
            new NewPostBlueprint($post),
            $notify->all()
        );
    }
}
