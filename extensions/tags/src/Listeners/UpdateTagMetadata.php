<?php namespace Flarum\Tags\Listeners;

use Flarum\Tags\Tag;
use Flarum\Tags\Events\DiscussionWasTagged;
use Flarum\Events\DiscussionWasStarted;
use Flarum\Events\DiscussionWasDeleted;
use Flarum\Core\Discussions\Discussion;
use Flarum\Core\Posts\Post;
use Flarum\Events\PostWasPosted;
use Flarum\Events\PostWasDeleted;
use Flarum\Events\PostWasHidden;
use Flarum\Events\PostWasRestored;

class UpdateTagMetadata
{
    public function subscribe($events)
    {
        $events->listen(DiscussionWasStarted::class, __CLASS__.'@whenDiscussionWasStarted');
        $events->listen(DiscussionWasTagged::class, __CLASS__.'@whenDiscussionWasTagged');
        $events->listen(DiscussionWasDeleted::class, __CLASS__.'@whenDiscussionWasDeleted');

        $events->listen(PostWasPosted::class, __CLASS__.'@whenPostWasPosted');
        $events->listen(PostWasDeleted::class, __CLASS__.'@whenPostWasDeleted');
        $events->listen(PostWasHidden::class, __CLASS__.'@whenPostWasHidden');
        $events->listen(PostWasRestored::class, __CLASS__.'@whenPostWasRestored');
    }

    public function whenDiscussionWasStarted(DiscussionWasStarted $event)
    {
        $this->updateTags($event->discussion, 1);
    }

    public function whenDiscussionWasTagged(DiscussionWasTagged $event)
    {
        $oldTags = Tag::whereIn('id', array_pluck($event->oldTags, 'id'));

        $this->updateTags($event->discussion, -1, $oldTags);

        $this->updateTags($event->discussion, 1);
    }

    public function whenDiscussionWasDeleted(DiscussionWasDeleted $event)
    {
        $this->updateTags($event->discussion, -1);

        $event->discussion->tags()->detach();
    }

    public function whenPostWasPosted(PostWasPosted $event)
    {
        $this->updateTags($event->post->discussion);
    }

    public function whenPostWasDeleted(PostWasDeleted $event)
    {
        $this->updateTags($event->post->discussion);
    }

    public function whenPostWasHidden(PostWasHidden $event)
    {
        $this->updateTags($event->post->discussion);
    }

    public function whenPostWasRestored(PostWasRestored $event)
    {
        $this->updateTags($event->post->discussion);
    }

    protected function updateTags($discussion, $delta = 0, $tags = null)
    {
        if (! $tags) {
            $tags = $discussion->tags;
        }

        foreach ($tags as $tag) {
            $tag->discussions_count += $delta;

            if ($discussion->last_time > $tag->last_time) {
                $tag->setLastDiscussion($discussion);
            } elseif ($discussion->id == $tag->last_discussion_id) {
                $tag->refreshLastDiscussion();
            }

            $tag->save();
        }
    }
}
