<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Extend;
use Flarum\Flags\Api\Resource\FlagResource;
use Flarum\Post\Filter\PostSearcher;
use Flarum\Post\Post;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\Tags\Access;
use Flarum\Tags\Api;
use Flarum\Tags\Content;
use Flarum\Tags\Event\DiscussionWasTagged;
use Flarum\Tags\Listener;
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
        ->post('/tags/order', 'tags.order', Api\Controller\OrderTagsController::class),

    (new Extend\Model(Discussion::class))
        ->belongsToMany('tags', Tag::class, 'discussion_tag'),

    new Extend\ApiResource(Api\Resource\TagResource::class),

    (new Extend\ApiResource(Resource\ForumResource::class))
        ->fields(fn () => [
            Schema\Relationship\ToMany::make('tags')
                ->includable()
                ->get(function ($model, Context $context) {
                    $actor = $context->getActor();

                    return Tag::query()
                        ->where(function ($query) {
                            $query
                                ->whereNull('parent_id')
                                ->whereNotNull('position');
                        })
                        ->union(
                            Tag::whereVisibleTo($actor)
                                ->whereNull('parent_id')
                                ->whereNull('position')
                                ->orderBy('discussion_count', 'desc')
                                ->limit(4) // We get one more than we need so the "more" link can be shown.
                        )
                        ->whereVisibleTo($actor)
                        ->withStateFor($actor)
                        ->get()
                        ->all();
                }),
            Schema\Boolean::make('canBypassTagCounts')
                ->get(fn ($model, Context $context) => $context->getActor()->can('bypassTagCounts')),
        ])
        ->endpoint(Endpoint\Show::class, function (Endpoint\Show $endpoint) {
            return $endpoint->addDefaultInclude(['tags', 'tags.parent']);
        }),

    (new Extend\ApiResource(Resource\PostResource::class))
        ->endpoint(Endpoint\Index::class, function (Endpoint\Index $endpoint) {
            return $endpoint->eagerLoadWhenIncluded(['discussion' => ['discussion.tags']]);
        }),

    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-flags', fn () => [
            (new Extend\ApiResource(FlagResource::class))
                ->endpoint(Endpoint\Index::class, function (Endpoint\Index $endpoint) {
                    return $endpoint->eagerLoadWhenIncluded(['post.discussion' => ['post.discussion.tags']]);
                }),
        ]),

    (new Extend\ApiResource(Resource\DiscussionResource::class))
        ->fields(Api\DiscussionResourceFields::class)
        ->endpoint(
            [Endpoint\Index::class, Endpoint\Show::class, Endpoint\Create::class],
            function (Endpoint\Index|Endpoint\Show|Endpoint\Create $endpoint) {
                return $endpoint
                    ->addDefaultInclude(['tags', 'tags.parent'])
                    ->eagerLoadWhere('tags', function (Builder|Relation $query, Context $context) {
                        /** @var Builder<Tag>|Relation $query */
                        $query->withStateFor($context->getActor());
                    });
            }
        ),

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

    (new Extend\ApiResource(Resource\PostResource::class))
        ->fields(fn () => [
            Schema\Relationship\ToMany::make('eventPostMentionsTags')
                ->type('tags')
                ->includable(),
        ])
        ->endpoint(Endpoint\Index::class, function (Endpoint\Index $endpoint) {
            return $endpoint
                ->addDefaultInclude(['eventPostMentionsTags']);
        }),
];
