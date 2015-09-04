<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Listeners;

use Flarum\Events\ApiRelationship;
use Flarum\Events\WillSerializeData;
use Flarum\Events\BuildApiAction;
use Flarum\Events\ApiAttributes;
use Flarum\Events\RegisterApiRoutes;
use Flarum\Api\Actions\Forum;
use Flarum\Api\Actions\Discussions;
use Flarum\Api\Serializers\ForumSerializer;
use Flarum\Api\Serializers\DiscussionSerializer;
use Flarum\Tags\Tag;

class AddApiAttributes
{
    public function subscribe($events)
    {
        $events->listen(ApiRelationship::class, [$this, 'addTagsRelationship']);
        $events->listen(WillSerializeData::class, [$this, 'loadTagsRelationship']);
        $events->listen(BuildApiAction::class, [$this, 'includeTagsRelationship']);
        $events->listen(ApiAttributes::class, [$this, 'addAttributes']);
        $events->listen(RegisterApiRoutes::class, [$this, 'addRoutes']);
    }

    public function addTagsRelationship(ApiRelationship $event)
    {
        if ($event->serializer instanceof ForumSerializer &&
            $event->relationship === 'tags') {
            return $event->serializer->hasMany('Flarum\Tags\Api\TagSerializer', 'tags');
        }

        if ($event->serializer instanceof DiscussionSerializer &&
            $event->relationship === 'tags') {
            return $event->serializer->hasMany('Flarum\Tags\Api\TagSerializer', 'tags');
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
            $event->action instanceof Discussions\ShowAction ||
            $event->action instanceof Discussions\CreateAction) {
            $event->addInclude('tags');
        }

        if ($event->action instanceof Discussions\CreateAction) {
            $event->addInclude('tags.lastDiscussion');
        }
    }

    public function addAttributes(ApiAttributes $event)
    {
        if ($event->serializer instanceof DiscussionSerializer) {
            $event->attributes['canTag'] = $event->model->can($event->actor, 'tag');
        }

        if ($event->serializer instanceof ForumSerializer) {
            $settings = app('Flarum\Core\Settings\SettingsRepository');

            $event->attributes['minPrimaryTags'] = $settings->get('tags.min_primary_tags');
            $event->attributes['maxPrimaryTags'] = $settings->get('tags.max_primary_tags');
            $event->attributes['minSecondaryTags'] = $settings->get('tags.min_secondary_tags');
            $event->attributes['maxSecondaryTags'] = $settings->get('tags.max_secondary_tags');
        }
    }

    public function addRoutes(RegisterApiRoutes $event)
    {
        $event->post('/tags', 'tags.create', 'Flarum\Tags\Api\CreateAction');
        $event->post('/tags/order', 'tags.order', 'Flarum\Tags\Api\OrderAction');
        $event->patch('/tags/{id}', 'tags.update', 'Flarum\Tags\Api\UpdateAction');
        $event->delete('/tags/{id}', 'tags.delete', 'Flarum\Tags\Api\DeleteAction');
    }
}
