<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Listener;

use Flarum\Discussion\Event\Deleted;
use Flarum\Discussion\Event\Started;
use Flarum\Post\Event\Deleted as PostDeleted;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Tags\Event\DiscussionWasTagged;
use Flarum\Tags\Tag;
use Illuminate\Contracts\Events\Dispatcher;

class UpdateTagMetadata
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(Started::class, [$this, 'whenDiscussionIsStarted']);
        $events->listen(DiscussionWasTagged::class, [$this, 'whenDiscussionWasTagged']);
        $events->listen(Deleted::class, [$this, 'whenDiscussionIsDeleted']);

        $events->listen(Posted::class, [$this, 'whenPostIsPosted']);
        $events->listen(PostDeleted::class, [$this, 'whenPostIsDeleted']);
        $events->listen(Hidden::class, [$this, 'whenPostIsHidden']);
        $events->listen(Restored::class, [$this, 'whenPostIsRestored']);
    }

    /**
     * @param Started $event
     */
    public function whenDiscussionIsStarted(Started $event)
    {
        $this->updateTags($event->discussion, 1);
    }

    /**
     * @param DiscussionWasTagged $event
     */
    public function whenDiscussionWasTagged(DiscussionWasTagged $event)
    {
        $oldTags = Tag::whereIn('id', array_pluck($event->oldTags, 'id'));

        $this->updateTags($event->discussion, -1, $oldTags);
        $this->updateTags($event->discussion, 1);
    }

    /**
     * @param Deleted $event
     */
    public function whenDiscussionIsDeleted(Deleted $event)
    {
        $this->updateTags($event->discussion, -1);

        $event->discussion->tags()->detach();
    }

    /**
     * @param Posted $event
     */
    public function whenPostIsPosted(Posted $event)
    {
        $this->updateTags($event->post->discussion);
    }

    /**
     * @param Deleted $event
     */
    public function whenPostIsDeleted(PostDeleted $event)
    {
        $this->updateTags($event->post->discussion);
    }

    /**
     * @param Hidden $event
     */
    public function whenPostIsHidden(Hidden $event)
    {
        $this->updateTags($event->post->discussion);
    }

    /**
     * @param Restored $event
     */
    public function whenPostIsRestored(Restored $event)
    {
        $this->updateTags($event->post->discussion);
    }

    /**
     * @param \Flarum\Discussion\Discussion $discussion
     * @param int $delta
     * @param Tag[]|null $tags
     */
    protected function updateTags($discussion, $delta = 0, $tags = null)
    {
        if (! $discussion) {
            return;
        }

        // We do not count private discussions in tags
        if ($discussion->is_private) {
            return;
        }

        if (! $tags) {
            $tags = $discussion->tags;
        }

        foreach ($tags as $tag) {
            $tag->discussion_count += $delta;

            if ($discussion->last_posted_at > $tag->last_posted_at) {
                $tag->setLastPostedDiscussion($discussion);
            } elseif ($discussion->id == $tag->last_posted_discussion_id) {
                $tag->refreshLastPostedDiscussion();
            }

            $tag->save();
        }
    }
}
