<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Controller as FlarumController;
use Flarum\Api\Serializer\DiscussionSerializer;
use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Saving;
use Flarum\Extend;
use Flarum\Tags\Access;
use Flarum\Tags\Api\Controller;
use Flarum\Tags\Api\Serializer\TagSerializer;
use Flarum\Tags\Content;
use Flarum\Tags\Listener;
use Flarum\Tags\LoadForumTagsRelationship;
use Flarum\Tags\Tag;
use Illuminate\Contracts\Events\Dispatcher;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less')
        ->route('/t/{slug}', 'tag', Content\Tag::class)
        ->route('/tags', 'tags', Content\Tags::class),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    (new Extend\Routes('api'))
        ->get('/tags', 'tags.index', Controller\ListTagsController::class)
        ->post('/tags', 'tags.create', Controller\CreateTagController::class)
        ->post('/tags/order', 'tags.order', Controller\OrderTagsController::class)
        ->patch('/tags/{id}', 'tags.update', Controller\UpdateTagController::class)
        ->delete('/tags/{id}', 'tags.delete', Controller\DeleteTagController::class),

    (new Extend\Model(Discussion::class))
        ->belongsToMany('tags', Tag::class, 'discussion_tag'),

    (new Extend\ApiSerializer(ForumSerializer::class))
        ->hasMany('tags', TagSerializer::class),

    (new Extend\ApiSerializer(DiscussionSerializer::class))
        ->hasMany('tags', TagSerializer::class)
        ->attribute('canTag', function (DiscussionSerializer $serializer, $model) {
            return $serializer->getActor()->can('tag', $model);
        }),

    (new Extend\ApiController(FlarumController\ListDiscussionsController::class))
        ->addInclude(['tags', 'tags.state']),

    (new Extend\ApiController(FlarumController\ShowDiscussionController::class))
        ->addInclude(['tags', 'tags.state']),

    (new Extend\ApiController(FlarumController\CreateDiscussionController::class))
        ->addInclude(['tags', 'tags.state']),

    (new Extend\ApiController(FlarumController\ShowForumController::class))
        ->addInclude(['tags', 'tags.lastPostedDiscussion', 'tags.parent'])
        ->prepareDataForSerialization(LoadForumTagsRelationship::class),

    (new Extend\Settings())
        ->serializeToForum('minPrimaryTags', 'flarum-tags.min_primary_tags')
        ->serializeToForum('maxPrimaryTags', 'flarum-tags.max_primary_tags')
        ->serializeToForum('minSecondaryTags', 'flarum-tags.min_secondary_tags')
        ->serializeToForum('maxSecondaryTags', 'flarum-tags.max_secondary_tags'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\View)
        ->namespace('tags', __DIR__.'/views'),

    function (Dispatcher $events) {
        $events->subscribe(Listener\CreatePostWhenTagsAreChanged::class);
        $events->subscribe(Listener\FilterDiscussionListByTags::class);
        $events->subscribe(Listener\FilterPostsQueryByTag::class);
        $events->listen(Saving::class, Listener\SaveTagsToDatabase::class);
        $events->subscribe(Listener\UpdateTagMetadata::class);

        $events->subscribe(Access\GlobalPolicy::class);
        $events->subscribe(Access\DiscussionPolicy::class);
        $events->subscribe(Access\TagPolicy::class);
        $events->subscribe(Access\FlagPolicy::class);
    },
];
