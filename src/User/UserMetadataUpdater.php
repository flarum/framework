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

use Flarum\Core\Discussion;
use Flarum\Core\Post;
use Flarum\Event\DiscussionWasDeleted;
use Flarum\Event\DiscussionWasStarted;
use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Illuminate\Contracts\Events\Dispatcher;

class UserMetadataUpdater
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
        $events->listen(DiscussionWasStarted::class, [$this, 'whenDiscussionWasStarted']);
        $events->listen(DiscussionWasDeleted::class, [$this, 'whenDiscussionWasDeleted']);
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
    public function whenPostWasDeleted(Deleted $event)
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
     * @param \Flarum\Event\DiscussionWasStarted $event
     */
    public function whenDiscussionWasStarted(DiscussionWasStarted $event)
    {
        $this->updateDiscussionsCount($event->discussion, 1);
    }

    /**
     * @param \Flarum\Event\DiscussionWasDeleted $event
     */
    public function whenDiscussionWasDeleted(DiscussionWasDeleted $event)
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
     * @param Discussion $discussion
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
