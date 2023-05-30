<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Lock\Listener;

use Flarum\Lock\Event\DiscussionWasUnlocked;
use Flarum\Lock\Notification\DiscussionLockedBlueprint;
use Flarum\Lock\Post\DiscussionLockedPost;
use Flarum\Notification\NotificationSyncer;

class CreatePostWhenDiscussionIsUnlocked
{
    public function __construct(
        protected NotificationSyncer $notifications
    ) {
    }

    public function handle(DiscussionWasUnlocked $event): void
    {
        $post = DiscussionLockedPost::reply(
            $event->discussion->id,
            $event->user->id,
            false
        );

        $post = $event->discussion->mergePost($post);

        if ($event->discussion->user_id !== $event->user->id) {
            $notification = new DiscussionLockedBlueprint($post);

            $this->notifications->sync($notification, $post->exists ? [$event->discussion->user] : []);
        }
    }
}
