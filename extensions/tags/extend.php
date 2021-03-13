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
use Flarum\Discussion\Filter\DiscussionFilterer;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Extend;
use Flarum\Flags\Flag;
use Flarum\Tags\Access;
use Flarum\Tags\Api\Controller;
use Flarum\Tags\Api\Serializer\TagSerializer;
use Flarum\Tags\Content;
use Flarum\Tags\Event\DiscussionWasTagged;
use Flarum\Tags\Filter\HideHiddenTagsFromAllDiscussionsPage;
use Flarum\Tags\Filter\PostTagFilter;
use Flarum\Tags\Listener;
use Flarum\Tags\LoadForumTagsRelationship;
use Flarum\Tags\Post\DiscussionTaggedPost;
use Flarum\Tags\Query\TagFilterGambit;
use Flarum\Tags\Tag;

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
        ->hasMany('tags', TagSerializer::class)
        ->attribute('canBypassTagCounts', function (ForumSerializer $serializer) {
            return $serializer->getActor()->can('bypassTagCounts');
        }),

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

    (new Extend\Policy())
        ->modelPolicy(Discussion::class, Access\DiscussionPolicy::class)
        ->modelPolicy(Tag::class, Access\TagPolicy::class)
        ->globalPolicy(Access\GlobalPolicy::class),

    (new Extend\ModelVisibility(Discussion::class))
        ->scope(Access\ScopeDiscussionVisibility::class)
        ->scopeAll(Access\ScopeDiscussionVisibilityForAbility::class),

    (new Extend\ModelVisibility(Flag::class))
        ->scope(Access\ScopeFlagVisibility::class),

    (new Extend\ModelVisibility(Tag::class))
        ->scope(Access\ScopeTagVisibility::class),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\View)
        ->namespace('tags', __DIR__.'/views'),

    (new Extend\Post)
        ->type(DiscussionTaggedPost::class),

    (new Extend\Event())
        ->listen(Saving::class, Listener\SaveTagsToDatabase::class)
        ->listen(DiscussionWasTagged::class, Listener\CreatePostWhenTagsAreChanged::class)
        ->subscribe(Listener\UpdateTagMetadata::class),

    (new Extend\Filter(PostFilterer::class))
        ->addFilter(PostTagFilter::class),

    (new Extend\Filter(DiscussionFilterer::class))
        ->addFilter(TagFilterGambit::class)
        ->addFilterMutator(HideHiddenTagsFromAllDiscussionsPage::class),

    (new Extend\SimpleFlarumSearch(DiscussionSearcher::class))
        ->addGambit(TagFilterGambit::class),
];
