<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Approval\Event\PostWasApproved;
use Flarum\Extend;
use Flarum\Group\Group;
use Flarum\Mentions\Api\PostResourceFields;
use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Post\Event\Revised;
use Flarum\Post\Filter\PostSearcher;
use Flarum\Post\Post;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\User\User;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    (new Extend\Formatter)
        ->configure(ConfigureMentions::class)
        ->parse(Formatter\EagerLoadMentionedModels::class)
        ->render(Formatter\FormatPostMentions::class)
        ->render(Formatter\FormatUserMentions::class)
        ->render(Formatter\FormatGroupMentions::class)
        ->unparse(Formatter\UnparsePostMentions::class)
        ->unparse(Formatter\UnparseUserMentions::class),

    (new Extend\Model(Post::class))
        ->belongsToMany('mentionedBy', Post::class, 'post_mentions_post', 'mentions_post_id', 'post_id')
        ->belongsToMany('mentionsPosts', Post::class, 'post_mentions_post', 'post_id', 'mentions_post_id')
        ->belongsToMany('mentionsUsers', User::class, 'post_mentions_user', 'post_id', 'mentions_user_id')
        ->belongsToMany('mentionsGroups', Group::class, 'post_mentions_group', 'post_id', 'mentions_group_id'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\View)
        ->namespace('flarum-mentions', __DIR__.'/views'),

    (new Extend\Notification())
        ->type(Notification\PostMentionedBlueprint::class, ['alert'])
        ->type(Notification\UserMentionedBlueprint::class, ['alert'])
        ->type(Notification\GroupMentionedBlueprint::class, ['alert']),

    (new Extend\ApiResource(Resource\PostResource::class))
        ->fields(PostResourceFields::class)
        ->endpoint([Endpoint\Index::class, Endpoint\Show::class], function (Endpoint\Index|Endpoint\Show $endpoint): Endpoint\Endpoint {
            return $endpoint->addDefaultInclude(['mentionedBy', 'mentionedBy.user', 'mentionedBy.discussion']);
        })
        ->endpoint(Endpoint\Index::class, function (Endpoint\Index $endpoint): Endpoint\Index {
            return $endpoint->eagerLoad(['mentionsUsers', 'mentionsPosts', 'mentionsPosts.user', 'mentionsPosts.discussion', 'mentionsGroups']);
        }),

    (new Extend\ApiResource(Resource\DiscussionResource::class))
        ->endpoint(Endpoint\Index::class, function (Endpoint\Index $endpoint): Endpoint\Index {
            return $endpoint->eagerLoadWhenIncluded([
                'firstPost' => [
                    'firstPost.mentionsUsers', 'firstPost.mentionsPosts',
                    'firstPost.mentionsPosts.user', 'firstPost.mentionsPosts.discussion', 'firstPost.mentionsGroups',
                ],
                'lastPost' => [
                    'lastPost.mentionsUsers', 'lastPost.mentionsPosts',
                    'lastPost.mentionsPosts.user', 'lastPost.mentionsPosts.discussion', 'lastPost.mentionsGroups',
                ],
            ]);
        })
        ->endpoint(Endpoint\Show::class, function (Endpoint\Show $endpoint): Endpoint\Show {
            return $endpoint->addDefaultInclude(['posts.mentionedBy', 'posts.mentionedBy.user', 'posts.mentionedBy.discussion'])
                ->eagerLoadWhenIncluded([
                    'posts' => [
                        'posts.mentionsUsers', 'posts.mentionsPosts', 'posts.mentionsPosts.user',
                        'posts.mentionsPosts.discussion', 'posts.mentionsGroups'
                    ],
                ]);
        }),

    (new Extend\ApiResource(Resource\UserResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('canMentionGroups')
                ->visible(fn (User $user, Context $context) => $context->getActor()->id === $user->id)
                ->get(fn (User $user) => $user->can('mentionGroups')),
        ]),

    (new Extend\Settings)
        ->serializeToForum('allowUsernameMentionFormat', 'flarum-mentions.allow_username_format', 'boolval'),

    (new Extend\Event())
        ->listen(Posted::class, Listener\UpdateMentionsMetadataWhenVisible::class)
        ->listen(Restored::class, Listener\UpdateMentionsMetadataWhenVisible::class)
        ->listen(Revised::class, Listener\UpdateMentionsMetadataWhenVisible::class)
        ->listen(PostWasApproved::class, Listener\UpdateMentionsMetadataWhenVisible::class)
        ->listen(Hidden::class, Listener\UpdateMentionsMetadataWhenInvisible::class)
        ->listen(Deleted::class, Listener\UpdateMentionsMetadataWhenInvisible::class),

    (new Extend\SearchDriver(DatabaseSearchDriver::class))
        ->addFilter(PostSearcher::class, Filter\MentionedFilter::class)
        ->addFilter(PostSearcher::class, Filter\MentionedPostFilter::class),

    // Tag mentions
    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-tags', fn () => [
            (new Extend\Formatter)
                ->render(Formatter\FormatTagMentions::class)
                ->unparse(Formatter\UnparseTagMentions::class),

            (new Extend\ApiResource(Resource\PostResource::class))
                ->fields(fn () => [
                    Schema\Relationship\ToMany::make('mentionsTags')
                        ->type('tags'),
                ]),

            (new Extend\ApiResource(Resource\DiscussionResource::class))
                ->endpoint(Endpoint\Show::class, function (Endpoint\Show $endpoint): Endpoint\Show {
                    return $endpoint->eagerLoadWhenIncluded(['posts' => ['posts.mentionsTags']]);
                })
                ->endpoint(Endpoint\Index::class, function (Endpoint\Index $endpoint): Endpoint\Index {
                    return $endpoint->eagerLoadWhenIncluded(['firstPost' => ['firstPost.mentionsTags'], 'lastPost' => ['lastPost.mentionsTags']]);
                }),

            (new Extend\ApiResource(Resource\PostResource::class))
                ->endpoint([Endpoint\Index::class, Endpoint\Show::class], function (Endpoint\Index|Endpoint\Show $endpoint): Endpoint\Endpoint {
                    return $endpoint->eagerLoad(['mentionsTags']);
                }),
        ]),
];
