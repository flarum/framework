<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Deleted as DiscussionDeleted;
use Flarum\Discussion\Event\Started;
use Flarum\Post\Event\Deleted as PostDeleted;
use Flarum\Post\Event\Posted;
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
        $events->listen(Started::class, [$this, 'whenDiscussionWasStarted']);
        $events->listen(DiscussionDeleted::class, [$this, 'whenDiscussionWasDeleted']);
    }

    /**
     * @param \Flarum\Post\Event\Posted $event
     */
    public function whenPostWasPosted(Posted $event)
    {
        $this->updateCommentsCount($event->post->user);
    }

    /**
     * @param \Flarum\Post\Event\Deleted $event
     */
    public function whenPostWasDeleted(PostDeleted $event)
    {
        $this->updateCommentsCount($event->post->user);
    }

    /**
     * @param \Flarum\Discussion\Event\Started $event
     */
    public function whenDiscussionWasStarted(Started $event)
    {
        $this->updateDiscussionsCount($event->discussion);
    }

    /**
     * @param \Flarum\Discussion\Event\Deleted $event
     */
    public function whenDiscussionWasDeleted(DiscussionDeleted $event)
    {
        $this->updateDiscussionsCount($event->discussion);
        $this->updateCommentsCount($event->discussion->user);
    }

    /**
     * @param \Flarum\User\User $user
     */
    private function updateCommentsCount(User $user)
    {
        if ($user && $user->exists) {
            $user->refreshCommentCount()->save();
        }
    }

    private function updateDiscussionsCount(Discussion $discussion)
    {
        $user = $discussion->user;

        if ($user && $user->exists) {
            $user->refreshDiscussionCount()->save();
        }
    }
}
