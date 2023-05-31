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
use Flarum\Post\Post;
use Flarum\Tags\Event\DiscussionWasTagged;
use Flarum\Tags\Tag;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class UpdateTagMetadata
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Started::class, $this->whenDiscussionIsStarted(...));
        $events->listen(DiscussionWasTagged::class, $this->whenDiscussionWasTagged(...));
        $events->listen(Deleted::class, $this->whenDiscussionIsDeleted(...));
        $events->listen(Hidden::class, $this->whenDiscussionIsHidden(...));
        $events->listen(Restored::class, $this->whenDiscussionIsRestored(...));

        $events->listen(Posted::class, $this->whenPostIsPosted(...));
        $events->listen(PostDeleted::class, $this->whenPostIsDeleted(...));
        $events->listen(PostHidden::class, $this->whenPostIsHidden(...));
        $events->listen(PostRestored::class, $this->whenPostIsRestored(...));
    }

    public function whenDiscussionIsStarted(Started $event): void
    {
        $this->updateTags($event->discussion, 1);
    }

    public function whenDiscussionWasTagged(DiscussionWasTagged $event): void
    {
        $oldTags = Tag::whereIn('id', Arr::pluck($event->oldTags, 'id'))->get();

        $this->updateTags($event->discussion, -1, $oldTags);
        $this->updateTags($event->discussion, 1);
    }

    public function whenDiscussionIsDeleted(Deleted $event): void
    {
        // If already soft deleted when permanently deleted, the -1 delta has already been applied in Hidden listener
        $delta = $event->discussion->hidden_at ? 0 : -1;
        $this->updateTags($event->discussion, $delta);

        $event->discussion->tags()->detach();
    }

    public function whenDiscussionIsHidden(Hidden $event): void
    {
        $this->updateTags($event->discussion, -1);
    }

    public function whenDiscussionIsRestored(Restored $event): void
    {
        $this->updateTags($event->discussion, 1);
    }

    public function whenPostIsPosted(Posted $event): void
    {
        $this->updateTags($event->post->discussion);
    }

    public function whenPostIsDeleted(PostDeleted $event): void
    {
        $discussion = $event->post->discussion;
        $delta = ! $discussion->exists && $discussion->hidden_at === null ? -1 : 0;
        $this->updateTags($discussion, $delta);
    }

    public function whenPostIsHidden(PostHidden $event): void
    {
        $this->updateTags($event->post->discussion, 0, null, $event->post);
    }

    public function whenPostIsRestored(PostRestored $event): void
    {
        $this->updateTags($event->post->discussion, 0, null, $event->post);
    }

    /**
     * @param Post|null $post This is only used when a post has been hidden
     */
    protected function updateTags(Discussion $discussion, int $delta = 0, ?Collection $tags = null, ?Post $post = null): void
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
