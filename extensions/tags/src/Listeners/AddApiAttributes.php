<?php namespace Flarum\Tags\Listeners;

use Flarum\Events\ApiRelationship;
use Flarum\Events\WillSerializeData;
use Flarum\Events\BuildApiAction;
use Flarum\Events\ApiAttributes;
use Flarum\Api\Actions\Forum;
use Flarum\Api\Actions\Discussions;
use Flarum\Api\Serializers\ForumSerializer;
use Flarum\Api\Serializers\DiscussionSerializer;
use Flarum\Tags\Tag;

class AddApiAttributes
{
    public function subscribe($events)
    {
        $events->listen(ApiRelationship::class, __CLASS__.'@addTagsRelationship');
        $events->listen(WillSerializeData::class, __CLASS__.'@loadTagsRelationship');
        $events->listen(BuildApiAction::class, __CLASS__.'@includeTagsRelationship');
        $events->listen(ApiAttributes::class, __CLASS__.'@addAttributes');
    }

    public function addTagsRelationship(ApiRelationship $event)
    {
        if ($event->serializer instanceof ForumSerializer &&
            $event->relationship === 'tags') {
            return $event->serializer->hasMany('Flarum\Tags\TagSerializer', 'tags');
        }

        if ($event->serializer instanceof DiscussionSerializer &&
            $event->relationship === 'tags') {
            return $event->serializer->hasMany('Flarum\Tags\TagSerializer', 'tags');
        }
    }

    public function loadTagsRelationship(WillSerializeData $event)
    {
        // Expose the complete tag list to clients by adding it as a
        // relationship to the /api/forum endpoint. Since the Forum model
        // doesn't actually have a tags relationship, we will manually load and
        // assign the tags data to it using an event listener.
        if ($event->action instanceof Forum\ShowAction) {
            $forum = $event->data;

            $query = Tag::whereVisibleTo($event->request->actor);

            $forum->tags = $query->with('lastDiscussion')->get();
            $forum->tags_ids = $forum->tags->lists('id');
        }
    }

    public function includeTagsRelationship(BuildApiAction $event)
    {
        if ($event->action instanceof Forum\ShowAction) {
            $event->addInclude('tags');
            $event->addInclude('tags.lastDiscussion');
            $event->addLink('tags.parent');
        }

        if ($event->action instanceof Discussions\IndexAction ||
            $event->action instanceof Discussions\ShowAction) {
            $event->addInclude('tags');
        }
    }

    public function addAttributes(ApiAttributes $event)
    {
        if ($event->serializer instanceof DiscussionSerializer) {
            $event->attributes['canTag'] = $event->model->can($event->actor, 'tag');
        }
    }
}
