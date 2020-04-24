<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Serializer\PostSerializer;
use Flarum\Event\ConfigureNotificationTypes;
use Flarum\Extend;
use Flarum\Likes\Event\PostWasLiked;
use Flarum\Likes\Event\PostWasUnliked;
use Flarum\Likes\Listener;
use Flarum\Likes\Notification\PostLikedBlueprint;
use Flarum\Post\Post;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    (new Extend\Model(Post::class))
        ->belongsToMany('likes', User::class, 'post_likes', 'post_id', 'user_id'),

    function (Dispatcher $events) {
        $events->subscribe(Listener\AddPostLikesRelationship::class);
        $events->subscribe(Listener\SaveLikesToDatabase::class);

        $events->listen(ConfigureNotificationTypes::class, function (ConfigureNotificationTypes $event) {
            $event->add(PostLikedBlueprint::class, PostSerializer::class, ['alert']);
        });
        $events->listen(PostWasLiked::class, Listener\SendNotificationWhenPostIsLiked::class);
        $events->listen(PostWasUnliked::class, Listener\SendNotificationWhenPostIsUnliked::class);
    },
];
