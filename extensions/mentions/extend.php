<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions;

use Flarum\Api\Controller;
use Flarum\Api\Serializer\BasicPostSerializer;
use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\Api\Serializer\CurrentUserSerializer;
use Flarum\Api\Serializer\GroupSerializer;
use Flarum\Api\Serializer\PostSerializer;
use Flarum\Extend;
use Flarum\Group\Group;
use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Post\Event\Revised;
use Flarum\Post\Filter\PostFilterer;
use Flarum\Post\Post;
use Flarum\User\User;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    (new Extend\Formatter)
        ->configure(ConfigureMentions::class)
        ->render(Formatter\FormatPostMentions::class)
        ->render(Formatter\FormatUserMentions::class)
        ->render(Formatter\FormatGroupMentions::class)
        ->unparse(Formatter\UnparsePostMentions::class)
        ->unparse(Formatter\UnparseUserMentions::class)
        ->parse(Formatter\CheckPermissions::class),

    (new Extend\Model(Post::class))
        ->belongsToMany('mentionedBy', Post::class, 'post_mentions_post', 'mentions_post_id', 'post_id')
        ->belongsToMany('mentionsPosts', Post::class, 'post_mentions_post', 'post_id', 'mentions_post_id')
        ->belongsToMany('mentionsUsers', User::class, 'post_mentions_user', 'post_id', 'mentions_user_id')
        ->belongsToMany('mentionsGroups', Group::class, 'post_mentions_group', 'post_id', 'mentions_group_id'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\View)
        ->namespace('flarum-mentions', __DIR__.'/views'),

    (new Extend\Notification())
        ->type(Notification\PostMentionedBlueprint::class, PostSerializer::class, ['alert'])
        ->type(Notification\UserMentionedBlueprint::class, PostSerializer::class, ['alert'])
        ->type(Notification\GroupMentionedBlueprint::class, PostSerializer::class, ['alert']),

    (new Extend\ApiSerializer(BasicPostSerializer::class))
        ->hasMany('mentionedBy', BasicPostSerializer::class)
        ->hasMany('mentionsPosts', BasicPostSerializer::class)
        ->hasMany('mentionsUsers', BasicUserSerializer::class)
        ->hasMany('mentionsGroups', GroupSerializer::class),

    (new Extend\ApiController(Controller\ShowDiscussionController::class))
        ->addInclude(['posts.mentionedBy', 'posts.mentionedBy.user', 'posts.mentionedBy.discussion'])
        ->load([
            'posts.mentionsUsers', 'posts.mentionsPosts', 'posts.mentionsPosts.user', 'posts.mentionedBy',
            'posts.mentionedBy.mentionsPosts', 'posts.mentionedBy.mentionsPosts.user', 'posts.mentionedBy.mentionsUsers',
            'posts.mentionsGroups'
        ]),

    (new Extend\ApiController(Controller\ListDiscussionsController::class))
        ->load([
            'firstPost.mentionsUsers', 'firstPost.mentionsPosts', 'firstPost.mentionsPosts.user', 'firstPost.mentionsGroups',
            'lastPost.mentionsUsers', 'lastPost.mentionsPosts', 'lastPost.mentionsPosts.user', 'lastPost.mentionsGroups'
        ]),

    (new Extend\ApiController(Controller\ShowPostController::class))
        ->addInclude(['mentionedBy', 'mentionedBy.user', 'mentionedBy.discussion']),

    (new Extend\ApiController(Controller\ListPostsController::class))
        ->addInclude(['mentionedBy', 'mentionedBy.user', 'mentionedBy.discussion'])
        ->load([
            'mentionsUsers', 'mentionsPosts', 'mentionsPosts.user', 'mentionedBy',
            'mentionedBy.mentionsPosts', 'mentionedBy.mentionsPosts.user', 'mentionedBy.mentionsUsers',
            'mentionsGroups'
        ]),

    (new Extend\ApiController(Controller\CreatePostController::class))
        ->addInclude(['mentionsPosts', 'mentionsPosts.mentionedBy'])
        ->addOptionalInclude('mentionsGroups'),

    (new Extend\ApiController(Controller\UpdatePostController::class))
        ->addInclude(['mentionsPosts', 'mentionsPosts.mentionedBy'])
        ->addOptionalInclude('mentionsGroups'),

    (new Extend\ApiController(Controller\AbstractSerializeController::class))
        ->prepareDataForSerialization(FilterVisiblePosts::class),

    (new Extend\Settings)
        ->serializeToForum('allowUsernameMentionFormat', 'flarum-mentions.allow_username_format', 'boolval'),

    (new Extend\Event())
        ->listen(Posted::class, Listener\UpdateMentionsMetadataWhenVisible::class)
        ->listen(Restored::class, Listener\UpdateMentionsMetadataWhenVisible::class)
        ->listen(Revised::class, Listener\UpdateMentionsMetadataWhenVisible::class)
        ->listen(Hidden::class, Listener\UpdateMentionsMetadataWhenInvisible::class)
        ->listen(Deleted::class, Listener\UpdateMentionsMetadataWhenInvisible::class),

    (new Extend\Filter(PostFilterer::class))
        ->addFilter(Filter\MentionedFilter::class),

    (new Extend\ApiSerializer(CurrentUserSerializer::class))
        ->attribute('canMentionGroups', function (CurrentUserSerializer $serializer, User $user, array $attributes): bool {
            return $user->can('mentionGroups');
        })
];
