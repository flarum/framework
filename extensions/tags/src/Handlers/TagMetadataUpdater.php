<?php namespace Flarum\Tags\Handlers;

use Flarum\Tags\Tag;
use Flarum\Tags\Events\DiscussionWasTagged;
use Flarum\Core\Events\DiscussionWasStarted;
use Flarum\Core\Events\DiscussionWasDeleted;
use Flarum\Core\Models\Discussion;

class TagMetadataUpdater
{
    public function subscribe($events)
    {
        $events->listen('Flarum\Core\Events\DiscussionWasStarted', __CLASS__.'@whenDiscussionWasStarted');
        $events->listen('Flarum\Tags\Events\DiscussionWasTagged', __CLASS__.'@whenDiscussionWasTagged');
        $events->listen('Flarum\Core\Events\DiscussionWasDeleted', __CLASS__.'@whenDiscussionWasDeleted');
    }

    public function whenDiscussionWasStarted(DiscussionWasStarted $event)
    {
        $tags = $event->discussion->tags();

        $this->updateTags($tags, 1, $event->discussion);
    }

    public function whenDiscussionWasTagged(DiscussionWasTagged $event)
    {
        $oldTags = Tag::whereIn('id', array_pluck($event->oldTags, 'id'));

        $this->updateTags($oldTags, -1, $event->discussion);

        $newTags = $event->discussion->tags();

        $this->updateTags($newTags, 1, $event->discussion);
    }

    public function whenDiscussionWasDeleted(DiscussionWasDeleted $event)
    {
        $tags = $event->discussion->tags();

        $this->updateTags($tags, -1, $event->discussion);

        $tags->detach();
    }

    protected function updateTags($query, $delta, $discussion)
    {
        foreach ($query->get() as $tag) {
            $tag->discussions_count += $delta;

            if ($delta > 0 && max($discussion->start_time, $discussion->last_time) > $tag->last_time) {
                $tag->setLastDiscussion($discussion);
            } elseif ($delta < 0 && $discussion->id == $tag->last_discussion_id) {
                $tag->refreshLastDiscussion();
            }

            $tag->save();
        }
    }
}
