<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Controller;
use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\Api\Serializer\PostSerializer;
use Flarum\Extend;
use Flarum\Likes\Event\PostWasLiked;
use Flarum\Likes\Event\PostWasUnliked;
use Flarum\Likes\Listener;
use Flarum\Likes\Notification\PostLikedBlueprint;
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
        ->belongsToMany('likes', User::class, 'post_likes', 'post_id', 'user_id'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Notification())
        ->type(PostLikedBlueprint::class, PostSerializer::class, ['alert']),

    (new Extend\ApiSerializer(PostSerializer::class))
        ->hasMany('likes', BasicUserSerializer::class)
        ->attribute('canLike', function (PostSerializer $serializer, $model) {
            return (bool) $serializer->getActor()->can('like', $model);
        }),

    (new Extend\ApiController(Controller\ShowDiscussionController::class))
        ->addInclude('posts.likes'),

    (new Extend\ApiController(Controller\ListPostsController::class))
        ->addInclude('likes'),
    (new Extend\ApiController(Controller\ShowPostController::class))
        ->addInclude('likes'),
    (new Extend\ApiController(Controller\CreatePostController::class))
        ->addInclude('likes'),
    (new Extend\ApiController(Controller\UpdatePostController::class))
        ->addInclude('likes'),

    (new Extend\Event())
        ->listen(PostWasLiked::class, Listener\SendNotificationWhenPostIsLiked::class)
        ->listen(PostWasUnliked::class, Listener\SendNotificationWhenPostIsUnliked::class)
        ->listen(Deleted::class, [Listener\SaveLikesToDatabase::class, 'whenPostIsDeleted'])
        ->listen(Saving::class, [Listener\SaveLikesToDatabase::class, 'whenPostIsSaving']),
];
