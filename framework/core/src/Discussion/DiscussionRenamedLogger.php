<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion;

use Flarum\Discussion\Event\Renamed;
use Flarum\Notification\Blueprint\DiscussionRenamedBlueprint;
use Flarum\Notification\NotificationSyncer;
use Flarum\Post\DiscussionRenamedPost;

class DiscussionRenamedLogger
{
    public function __construct(
        protected NotificationSyncer $notifications
    ) {
    }

    public function handle(Renamed $event): void
    {
        $post = DiscussionRenamedPost::reply(
            $event->discussion->id,
            $event->actor->id,
            $event->oldTitle,
            $event->discussion->title
        );

        /** @var DiscussionRenamedPost $post */
        $post = $event->discussion->mergePost($post);

        if ($event->discussion->user_id !== $event->actor->id) {
            $blueprint = new DiscussionRenamedBlueprint($post);

            if ($post->exists) {
                $this->notifications->sync($blueprint, [$event->discussion->user]);
            } else {
                $this->notifications->delete($blueprint);
            }
        }
    }
}
