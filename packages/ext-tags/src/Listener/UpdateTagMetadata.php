<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Listener;

use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Deleted;
use Flarum\Discussion\Event\Hidden;
use Flarum\Discussion\Event\Restored;
use Flarum\Discussion\Event\Started;
use Flarum\Post\Event\Deleted as PostDeleted;
use Flarum\Post\Event\Hidden as PostHidden;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored as PostRestored;
use Flarum\Tags\Event\DiscussionWasTagged;
use Flarum\Tags\Tag;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;

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
        $events->listen(Hidden::class, [$this, 'whenDiscussionIsHidden']);
        $events->listen(Restored::class, [$this, 'whenDiscussionIsRestored']);

        $events->listen(Posted::class, [$this, 'whenPostIsPosted']);
        $events->listen(PostDeleted::class, [$this, 'whenPostIsDeleted']);
        $events->listen(PostHidden::class, [$this, 'whenPostIsHidden']);
        $events->listen(PostRestored::class, [$this, 'whenPostIsRestored']);
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
        $oldTags = Tag::whereIn('id', Arr::pluck($event->oldTags, 'id'))->get();

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
     * @param Hidden $event
     */
    public function whenDiscussionIsHidden(Hidden $event)
    {
        $this->updateTags($event->discussion, -1);
    }

    /**
     * @param Restored $event
     */
    public function whenDiscussionIsRestored(Restored $event)
    {
        $this->updateTags($event->discussion, 1);
    }

    /**
     * @param Posted $event
     */
    public function whenPostIsPosted(Posted $event)
    {
        $this->updateTags($event->post->discussion);
    }

    /**
     * @param PostDeleted $event
     */
    public function whenPostIsDeleted(PostDeleted $event)
    {
        $discussion = $event->post->discussion;
        $delta = ! $discussion->exists && $discussion->hidden_at === null ? -1 : 0;
        $this->updateTags($discussion, $delta);
    }

    /**
     * @param PostHidden $event
     */
    public function whenPostIsHidden(PostHidden $event)
    {
        $this->updateTags($event->post->discussion, 0, null, $event->post);
    }

    /**
     * @param PostRestored $event
     */
    public function whenPostIsRestored(PostRestored $event)
    {
        $this->updateTags($event->post->discussion, 0, null, $event->post);
    }

    /**
     * @param \Flarum\Discussion\Discussion $discussion
     * @param int $delta
     * @param Tag[]|null $tags
     * @param Post $post: This is only used when a post has been hidden
     */
    protected function updateTags(Discussion $discussion, $delta = 0, $tags = null, $post = null)
    {
        if (! $tags) {
            $tags = $discussion->tags;
        }

        // If we've just hidden or restored a post, the discussion's last posted at metadata might not have updated yet.
        // Therefore, we must refresh the last post, even though that might be repeated in the future.
        if ($post) {
            $discussion->refreshLastPost();
        }

        foreach ($tags as $tag) {
            // We do not count private discussions or hidden discussions in tags
            if (! $discussion->is_private) {
                $tag->discussion_count += $delta;
            }

            // If this is a new / restored discussion, it isn't private, it isn't null,
            // and it's more recent than what we have now, set it as last posted discussion.
            if ($delta >= 0 && ! $discussion->is_private && $discussion->hidden_at == null && ($discussion->last_posted_at >= $tag->last_posted_at) && $discussion->exists) {
                $tag->setLastPostedDiscussion($discussion);
            } elseif ($discussion->id == $tag->last_posted_discussion_id) {
                // This is to persist refreshLastPost above. It is here instead of there so that
                // if it's not necessary, we save a DB query.
                if ($post) {
                    $discussion->save();
                }
                // This discussion is currently the last posted discussion, but since it didn't qualify for the above check,
                // it should not be the last posted discussion. Therefore, we should refresh the last posted discussion..
                $tag->refreshLastPostedDiscussion();
            }

            $tag->save();
        }
    }
}
