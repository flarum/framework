<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes;

use Flarum\Api\Controller;
use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\Api\Serializer\PostSerializer;
use Flarum\Extend;
use Flarum\Likes\Api\LoadLikesRelationship;
use Flarum\Likes\Event\PostWasLiked;
use Flarum\Likes\Event\PostWasUnliked;
use Flarum\Likes\Notification\PostLikedBlueprint;
use Flarum\Likes\Query\LikedByFilter;
use Flarum\Likes\Query\LikedFilter;
use Flarum\Post\Filter\PostFilterer;
use Flarum\Post\Post;
use Flarum\User\Filter\UserFilterer;
use Flarum\User\User;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    (new Extend\Model(Post::class))
        ->belongsToMany('likes', User::class, 'post_likes', 'post_id', 'user_id'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Notification())
        ->type(PostLikedBlueprint::class, PostSerializer::class, ['alert']),

    (new Extend\ApiSerializer(PostSerializer::class))
        ->hasMany('likes', BasicUserSerializer::class)
        ->attribute('canLike', function (PostSerializer $serializer, $model) {
            return (bool) $serializer->getActor()->can('like', $model);
        })
        ->attribute('likesCount', function (PostSerializer $serializer, $model) {
            return $model->getAttribute('likes_count') ?: 0;
        }),

    (new Extend\ApiController(Controller\ShowDiscussionController::class))
        ->addInclude('posts.likes')
        ->loadWhere('posts.likes', [LoadLikesRelationship::class, 'mutateRelation'])
        ->prepareDataForSerialization([LoadLikesRelationship::class, 'countRelation']),

    (new Extend\ApiController(Controller\ListPostsController::class))
        ->addInclude('likes')
        ->loadWhere('likes', [LoadLikesRelationship::class, 'mutateRelation'])
        ->prepareDataForSerialization([LoadLikesRelationship::class, 'countRelation']),
    (new Extend\ApiController(Controller\ShowPostController::class))
        ->addInclude('likes')
        ->loadWhere('likes', [LoadLikesRelationship::class, 'mutateRelation'])
        ->prepareDataForSerialization([LoadLikesRelationship::class, 'countRelation']),
    (new Extend\ApiController(Controller\CreatePostController::class))
        ->addInclude('likes')
        ->loadWhere('likes', [LoadLikesRelationship::class, 'mutateRelation'])
        ->prepareDataForSerialization([LoadLikesRelationship::class, 'countRelation']),
    (new Extend\ApiController(Controller\UpdatePostController::class))
        ->addInclude('likes')
        ->loadWhere('likes', [LoadLikesRelationship::class, 'mutateRelation'])
        ->prepareDataForSerialization([LoadLikesRelationship::class, 'countRelation']),

    (new Extend\Event())
        ->listen(PostWasLiked::class, Listener\SendNotificationWhenPostIsLiked::class)
        ->listen(PostWasUnliked::class, Listener\SendNotificationWhenPostIsUnliked::class)
        ->subscribe(Listener\SaveLikesToDatabase::class),

    (new Extend\Filter(PostFilterer::class))
        ->addFilter(LikedByFilter::class),

    (new Extend\Filter(UserFilterer::class))
        ->addFilter(LikedFilter::class),

    (new Extend\Settings())
        ->default('flarum-likes.like_own_post', true),

    (new Extend\Policy())
        ->modelPolicy(Post::class, Access\LikePostPolicy::class),
];
