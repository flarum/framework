<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(Posted::class, [$this, 'whenPostWasPosted']);
        $events->listen(Deleted::class, [$this, 'whenPostWasDeleted']);
        $events->listen(Hidden::class, [$this, 'whenPostWasHidden']);
        $events->listen(Restored::class, [$this, 'whenPostWasRestored']);
    }

    /**
     * @param Posted $event
     */
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

    /**
     * @param \Flarum\Post\Event\Deleted $event
     */
    public function whenPostWasDeleted(Deleted $event)
    {
        $this->removePost($event->post);

        $discussion = $event->post->discussion;

        if ($discussion && $discussion->posts()->count() === 0) {
            $discussion->delete();
        }
    }

    /**
     * @param \Flarum\Post\Event\Hidden $event
     */
    public function whenPostWasHidden(Hidden $event)
    {
        $this->removePost($event->post);
    }

    /**
     * @param Restored $event
     */
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

    /**
     * @param Post $post
     */
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
