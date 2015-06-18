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

        $this->updateTagCounts($tags, 1);
    }

    public function whenDiscussionWasTagged(DiscussionWasTagged $event)
    {
        $oldTags = Tag::whereIn('id', array_pluck($event->oldTags, 'id'));

        $this->updateTagCounts($oldTags, -1);

        $newTags = $event->discussion->tags();

        $this->updateTagCounts($newTags, 1);
    }

    public function whenDiscussionWasDeleted(DiscussionWasDeleted $event)
    {
        $tags = $event->discussion->tags();

        $this->updateTagCounts($tags, -1);

        $tags->detach();
    }

    protected function updateTagCounts($query, $delta)
    {
        $query->update(['discussions_count' => app('db')->raw('discussions_count + '.$delta)]);
    }
}
