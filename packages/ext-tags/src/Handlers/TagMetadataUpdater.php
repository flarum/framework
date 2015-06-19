<?php namespace Flarum\Tags\Handlers;

use Flarum\Tags\Tag;
use Flarum\Tags\Events\DiscussionWasTagged;
use Flarum\Core\Events\DiscussionWasStarted;
use Flarum\Core\Events\DiscussionWasDeleted;
use Flarum\Core\Models\Discussion;
use Flarum\Core\Models\Post;
use Flarum\Core\Events\PostWasPosted;
use Flarum\Core\Events\PostWasDeleted;
use Flarum\Core\Events\PostWasHidden;
use Flarum\Core\Events\PostWasRestored;

class TagMetadataUpdater
{
    public function subscribe($events)
    {
        $events->listen('Flarum\Core\Events\DiscussionWasStarted', __CLASS__.'@whenDiscussionWasStarted');
        $events->listen('Flarum\Tags\Events\DiscussionWasTagged', __CLASS__.'@whenDiscussionWasTagged');
        $events->listen('Flarum\Core\Events\DiscussionWasDeleted', __CLASS__.'@whenDiscussionWasDeleted');

        $events->listen('Flarum\Core\Events\PostWasPosted', __CLASS__.'@whenPostWasPosted');
        $events->listen('Flarum\Core\Events\PostWasDeleted', __CLASS__.'@whenPostWasDeleted');
        $events->listen('Flarum\Core\Events\PostWasHidden', __CLASS__.'@whenPostWasHidden');
        $events->listen('Flarum\Core\Events\PostWasRestored', __CLASS__.'@whenPostWasRestored');
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
            $tags = $discussion->getRelation('tags');
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
