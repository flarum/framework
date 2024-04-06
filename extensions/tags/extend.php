<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Controller as FlarumController;
use Flarum\Api\Serializer\BasicPostSerializer;
use Flarum\Api\Serializer\DiscussionSerializer;
use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Saving;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Extend;
use Flarum\Flags\Api\Controller\ListFlagsController;
use Flarum\Http\RequestUtil;
use Flarum\Post\Filter\PostSearcher;
use Flarum\Post\Post;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\Tags\Access;
use Flarum\Tags\Api\Controller;
use Flarum\Tags\Api\Serializer\TagSerializer;
use Flarum\Tags\Content;
use Flarum\Tags\Event\DiscussionWasTagged;
use Flarum\Tags\Listener;
use Flarum\Tags\LoadForumTagsRelationship;
use Flarum\Tags\Post\DiscussionTaggedPost;
use Flarum\Tags\Search\Filter\PostTagFilter;
use Flarum\Tags\Search\Filter\TagFilter;
use Flarum\Tags\Search\FulltextFilter;
use Flarum\Tags\Search\HideHiddenTagsFromAllDiscussionsPage;
use Flarum\Tags\Search\TagSearcher;
use Flarum\Tags\Tag;
use Flarum\Tags\Utf8SlugDriver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Psr\Http\Message\ServerRequestInterface;

$eagerLoadTagState = function ($query, ?ServerRequestInterface $request, array $relations) {
    if ($request && in_array('tags.state', $relations, true)) {
        $query->withStateFor(RequestUtil::getActor($request));
    }
};

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->jsDirectory(__DIR__.'/js/dist/forum')
        ->css(__DIR__.'/less/forum.less')
        ->route('/t/{slug}', 'tag', Content\Tag::class)
        ->route('/tags', 'tags', Content\Tags::class),

    (new Extend\Frontend('common'))
        ->jsDirectory(__DIR__.'/js/dist/common'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    (new Extend\Routes('api'))
        ->get('/tags', 'tags.index', Controller\ListTagsController::class)
        ->post('/tags', 'tags.create', Controller\CreateTagController::class)
        ->post('/tags/order', 'tags.order', Controller\OrderTagsController::class)
        ->get('/tags/{slug}', 'tags.show', Controller\ShowTagController::class)
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

    (new Extend\ApiController(FlarumController\ListPostsController::class))
        ->load('discussion.tags'),

    (new Extend\ApiController(ListFlagsController::class))
        ->load('post.discussion.tags'),

    (new Extend\ApiController(FlarumController\ListDiscussionsController::class))
        ->addInclude(['tags', 'tags.state', 'tags.parent'])
        ->loadWhere('tags', $eagerLoadTagState),

    (new Extend\ApiController(FlarumController\ShowDiscussionController::class))
        ->addInclude(['tags', 'tags.state', 'tags.parent'])
        ->loadWhere('tags', $eagerLoadTagState),

    (new Extend\ApiController(FlarumController\CreateDiscussionController::class))
        ->addInclude(['tags', 'tags.state', 'tags.parent'])
        ->loadWhere('tags', $eagerLoadTagState),

    (new Extend\ApiController(FlarumController\ShowForumController::class))
        ->addInclude(['tags', 'tags.parent'])
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
        ->scopeAll(Access\ScopeDiscussionVisibilityForAbility::class),

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

    (new Extend\SearchDriver(DatabaseSearchDriver::class))
        ->addFilter(PostSearcher::class, PostTagFilter::class)
        ->addFilter(DiscussionSearcher::class, TagFilter::class)
        ->addMutator(DiscussionSearcher::class, HideHiddenTagsFromAllDiscussionsPage::class)
        ->addSearcher(Tag::class, TagSearcher::class)
        ->setFulltext(TagSearcher::class, FulltextFilter::class),

    (new Extend\ModelUrl(Tag::class))
        ->addSlugDriver('default', Utf8SlugDriver::class),

    /*
     * Fixes DiscussionTaggedPost showing tags as deleted because they are not loaded in the store.
     * @link https://github.com/flarum/framework/issues/3620#issuecomment-1232911734
     */

    (new Extend\Model(Post::class))
        ->belongsToMany('mentionsTags', Tag::class, 'post_mentions_tag', 'post_id', 'mentions_tag_id')
        // We do not wish to include all `mentionsTags` in the API response,
        // only those related to `discussionTagged` posts.
        ->relationship('eventPostMentionsTags', function (Post $model) {
            return $model->mentionsTags();
        }),

    (new Extend\ApiSerializer(BasicPostSerializer::class))
        ->relationship('eventPostMentionsTags', function (BasicPostSerializer $serializer, Post $model) {
            if ($model instanceof DiscussionTaggedPost) {
                return $serializer->hasMany($model, TagSerializer::class, 'eventPostMentionsTags');
            }

            return null;
        })
        ->hasMany('eventPostMentionsTags', TagSerializer::class),

    (new Extend\ApiController(FlarumController\ListPostsController::class))
        ->addInclude('eventPostMentionsTags')
        // Restricted tags should still appear as `deleted` to unauthorized users.
        ->loadWhere('eventPostMentionsTags', $restrictMentionedTags = function (Relation|Builder $query, ?ServerRequestInterface $request) {
            if ($request) {
                $actor = RequestUtil::getActor($request);
                $query->whereVisibleTo($actor);
            }
        }),

    (new Extend\ApiController(FlarumController\ShowDiscussionController::class))
        ->addInclude('posts.eventPostMentionsTags')
        ->loadWhere('posts.eventPostMentionsTags', $restrictMentionedTags),
];
