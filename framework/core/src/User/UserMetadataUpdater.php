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
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Posted::class, $this->whenPostWasPosted(...));
        $events->listen(PostDeleted::class, $this->whenPostWasDeleted(...));
        $events->listen(Started::class, $this->whenDiscussionWasStarted(...));
        $events->listen(DiscussionDeleted::class, $this->whenDiscussionWasDeleted(...));
    }

    public function whenPostWasPosted(Posted $event): void
    {
        $this->updateCommentsCount($event->post->user);
    }

    public function whenPostWasDeleted(PostDeleted $event): void
    {
        $this->updateCommentsCount($event->post->user);
    }

    public function whenDiscussionWasStarted(Started $event): void
    {
        $this->updateDiscussionsCount($event->discussion);
    }

    public function whenDiscussionWasDeleted(DiscussionDeleted $event): void
    {
        $this->updateDiscussionsCount($event->discussion);
        $this->updateCommentsCount($event->discussion->user);
    }

    private function updateCommentsCount(?User $user): void
    {
        if ($user && $user->exists) {
            $user->refreshCommentCount()->save();
        }
    }

    private function updateDiscussionsCount(Discussion $discussion): void
    {
        $user = $discussion->user;

        if ($user && $user->exists) {
            $user->refreshDiscussionCount()->save();
        }
    }
}
