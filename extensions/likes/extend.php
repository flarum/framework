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
use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\Saving;
use Flarum\Post\Post;
use Flarum\User\User;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    (new Extend\Model(Post::class))
        ->relationship('recentLikes', Model\RecentLikesRelationship::class)
        ->belongsToMany('likes', User::class, 'post_likes', 'post_id', 'user_id'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Notification())
        ->type(Notification\PostLikedBlueprint::class, PostSerializer::class, ['alert']),

    (new Extend\ApiSerializer(PostSerializer::class))
        ->relationship('recentLikes', Api\RecentLikesRelationship::class)
        ->attributes(function (PostSerializer $serializer, $model, $attributes) {
            if ($model->likes_count) $attributes['likesCount'] = $model->likes_count;
            return $attributes;
        })
        ->hasMany('likes', BasicUserSerializer::class)
        ->attribute('canLike', function (PostSerializer $serializer, $model) {
            return $serializer->getActor()->can('like', $model);
        }),

    (new Extend\ApiController(Controller\ShowDiscussionController::class))
        ->addOptionalInclude('posts.likes')
        ->addInclude('posts.recentLikes'),

    (new Extend\ApiController(Controller\ListPostsController::class))
        ->addOptionalInclude('likes')
        ->addInclude('recentLikes'),
    (new Extend\ApiController(Controller\ShowPostController::class))
        ->addOptionalInclude('likes')
        ->addInclude('recentLikes'),
    (new Extend\ApiController(Controller\CreatePostController::class))
        ->addOptionalInclude('likes')
        ->addInclude('recentLikes'),
    (new Extend\ApiController(Controller\UpdatePostController::class))
        ->addOptionalInclude('likes')
        ->addInclude('recentLikes'),

    (new Extend\Event())
        ->listen(Event\PostWasLiked::class, Listener\SendNotificationWhenPostIsLiked::class)
        ->listen(Event\PostWasUnliked::class, Listener\SendNotificationWhenPostIsUnliked::class)
        ->listen(Deleted::class, [Listener\SaveLikesToDatabase::class, 'whenPostIsDeleted'])
        ->listen(Saving::class, [Listener\SaveLikesToDatabase::class, 'whenPostIsSaving']),
];
