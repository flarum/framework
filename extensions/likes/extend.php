<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes;

use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Extend;
use Flarum\Likes\Api\PostResourceFields;
use Flarum\Likes\Event\PostWasLiked;
use Flarum\Likes\Event\PostWasUnliked;
use Flarum\Likes\Notification\PostLikedBlueprint;
use Flarum\Likes\Query\LikedByFilter;
use Flarum\Likes\Query\LikedFilter;
use Flarum\Post\Event\Deleted;
use Flarum\Post\Filter\PostSearcher;
use Flarum\Post\Post;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\User\Search\UserSearcher;
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
        ->type(PostLikedBlueprint::class, ['alert']),

    (new Extend\ApiResource(Resource\PostResource::class))
        ->fields(PostResourceFields::class)
        ->endpoint(
            [Endpoint\Index::class, Endpoint\Show::class, Endpoint\Create::class, Endpoint\Update::class],
            function (Endpoint\Index|Endpoint\Show|Endpoint\Create|Endpoint\Update $endpoint): Endpoint\Endpoint {
                return $endpoint->addDefaultInclude(['likes']);
            }
        ),

    (new Extend\ApiResource(Resource\DiscussionResource::class))
        ->endpoint(Endpoint\Show::class, function (Endpoint\Show $endpoint): Endpoint\Endpoint {
            return $endpoint->addDefaultInclude(['posts.likes']);
        }),

    (new Extend\Event())
        ->listen(PostWasLiked::class, Listener\SendNotificationWhenPostIsLiked::class)
        ->listen(PostWasUnliked::class, Listener\SendNotificationWhenPostIsUnliked::class)
        ->listen(Deleted::class, function (Deleted $event) {
            $event->post->likes()->detach();
        }),

    (new Extend\SearchDriver(DatabaseSearchDriver::class))
        ->addFilter(PostSearcher::class, LikedByFilter::class)
        ->addFilter(UserSearcher::class, LikedFilter::class),

    (new Extend\Settings())
        ->default('flarum-likes.like_own_post', true),

    (new Extend\Policy())
        ->modelPolicy(Post::class, Access\LikePostPolicy::class),
];
