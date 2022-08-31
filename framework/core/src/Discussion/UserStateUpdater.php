<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion;

use Flarum\Post\Event\Deleted;
use Illuminate\Contracts\Events\Dispatcher;

class UserStateUpdater
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(Deleted::class, [$this, 'whenPostWasDeleted']);
    }

    /**
     * Updates a user state relative to a discussion.
     * If user A read a discussion all the way to post number N, and X last posts were deleted,
     * then we need to update user A's read status to the new N-X post number so that they get notified by new posts.
     */
    public function whenPostWasDeleted(Deleted $event)
    {
        /*
         * We check if it's greater because at this point the DiscussionMetadataUpdater should have updated the last post.
         */
        if ($event->post->number > $event->post->discussion->last_post_number) {
            UserState::query()
                ->where('discussion_id', $event->post->discussion_id)
                ->where('last_read_post_number', '>', $event->post->discussion->last_post_number)
                ->update(['last_read_post_number' => $event->post->discussion->last_post_number]);
        }
    }
}
