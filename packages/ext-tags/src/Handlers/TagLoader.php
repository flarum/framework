<?php namespace Flarum\Tags\Handlers;

use Flarum\Api\Events\WillSerializeData;
use Flarum\Api\Actions\Forum\ShowAction as ForumShowAction;
use Flarum\Tags\Tag;

class TagLoader
{
    public function subscribe($events)
    {
        $events->listen('Flarum\Api\Events\WillSerializeData', __CLASS__.'@whenWillSerializeData');
    }

    public function whenWillSerializeData(WillSerializeData $event)
    {
        if ($event->action instanceof ForumShowAction) {
            $forum = $event->data;

            $query = Tag::whereVisibleTo($event->request->actor->getUser());

            $forum->tags = $query->with('lastDiscussion')->get();
            $forum->tags_ids = $forum->tags->lists('id');
        }
    }
}
