<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion;

use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Post\Post;
use Illuminate\Contracts\Events\Dispatcher;

class DiscussionMetadataUpdater
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(Posted::class, [$this, 'whenPostWasPosted']);
        $events->listen(Deleted::class, [$this, 'whenPostWasDeleted']);
        $events->listen(Hidden::class, [$this, 'whenPostWasHidden']);
        $events->listen(Restored::class, [$this, 'whenPostWasRestored']);
    }

    public function whenPostWasPosted(Posted $event)
    {
        $discussion = $event->post->discussion;

        if ($discussion && $discussion->exists) {
            $discussion->refreshCommentCount();
            $discussion->refreshLastPost();
            $discussion->refreshParticipantCount();
            $discussion->save();
        }
    }

    public function whenPostWasDeleted(Deleted $event)
    {
        $this->removePost($event->post);

        $discussion = $event->post->discussion;

        if ($discussion && $discussion->posts()->count() === 0) {
            $discussion->delete();
        }
    }

    public function whenPostWasHidden(Hidden $event)
    {
        $this->removePost($event->post);
    }

    public function whenPostWasRestored(Restored $event)
    {
        $discussion = $event->post->discussion;

        if ($discussion && $discussion->exists) {
            $discussion->refreshCommentCount();
            $discussion->refreshParticipantCount();
            $discussion->refreshLastPost();
            $discussion->save();
        }
    }

    protected function removePost(Post $post)
    {
        $discussion = $post->discussion;

        if ($discussion && $discussion->exists) {
            $discussion->refreshCommentCount();
            $discussion->refreshParticipantCount();

            if ($discussion->last_post_id == $post->id) {
                $discussion->refreshLastPost();
            }

            $discussion->save();
        }
    }
}
