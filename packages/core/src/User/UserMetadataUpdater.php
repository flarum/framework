<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Deleted as DiscussionDeleted;
use Flarum\Discussion\Event\Started;
use Flarum\Post\Event\Deleted as PostDeleted;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Post\Post;
use Illuminate\Contracts\Events\Dispatcher;

class UserMetadataUpdater
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(Posted::class, [$this, 'whenPostWasPosted']);
        $events->listen(PostDeleted::class, [$this, 'whenPostWasDeleted']);
        $events->listen(Hidden::class, [$this, 'whenPostWasHidden']);
        $events->listen(Restored::class, [$this, 'whenPostWasRestored']);
        $events->listen(Started::class, [$this, 'whenDiscussionWasStarted']);
        $events->listen(DiscussionDeleted::class, [$this, 'whenDiscussionWasDeleted']);
    }

    /**
     * @param \Flarum\Post\Event\Posted $event
     */
    public function whenPostWasPosted(Posted $event)
    {
        $this->updateCommentsCount($event->post, 1);
    }

    /**
     * @param \Flarum\Post\Event\Deleted $event
     */
    public function whenPostWasDeleted(PostDeleted $event)
    {
        $this->updateCommentsCount($event->post, -1);
    }

    /**
     * @param \Flarum\Post\Event\Hidden $event
     */
    public function whenPostWasHidden(Hidden $event)
    {
        $this->updateCommentsCount($event->post, -1);
    }

    /**
     * @param \Flarum\Post\Event\Restored $event
     */
    public function whenPostWasRestored(Restored $event)
    {
        $this->updateCommentsCount($event->post, 1);
    }

    /**
     * @param \Flarum\Discussion\Event\Started $event
     */
    public function whenDiscussionWasStarted(Started $event)
    {
        $this->updateDiscussionsCount($event->discussion, 1);
    }

    /**
     * @param \Flarum\Discussion\Event\Deleted $event
     */
    public function whenDiscussionWasDeleted(DiscussionDeleted $event)
    {
        $this->updateDiscussionsCount($event->discussion, -1);
    }

    /**
     * @param Post $post
     * @param int $amount
     */
    protected function updateCommentsCount(Post $post, $amount)
    {
        $user = $post->user;

        if ($user && $user->exists) {
            $user->comments_count += $amount;
            $user->save();
        }
    }

    /**
     * @param \Flarum\Discussion\Discussion $discussion
     * @param int $amount
     */
    protected function updateDiscussionsCount(Discussion $discussion, $amount)
    {
        $user = $discussion->startUser;

        if ($user && $user->exists) {
            $user->discussions_count += $amount;
            $user->save();
        }
    }
}
