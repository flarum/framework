<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Mentions\Listener;

use Flarum\Api\Controller\CreatePostController;
use Flarum\Api\Controller\ListPostsController;
use Flarum\Api\Controller\ShowDiscussionController;
use Flarum\Api\Controller\ShowPostController;
use Flarum\Api\Serializer\PostBasicSerializer;
use Flarum\Core\Post;
use Flarum\Core\User;
use Flarum\Event\ConfigureApiController;
use Flarum\Event\GetApiRelationship;
use Flarum\Event\GetModelRelationship;
use Illuminate\Contracts\Events\Dispatcher;

class AddPostMentionedByRelationship
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(GetModelRelationship::class, [$this, 'getModelRelationship']);
        $events->listen(GetApiRelationship::class, [$this, 'getApiRelationship']);
        $events->listen(ConfigureApiController::class, [$this, 'includeRelationships']);
    }

    /**
     * @param GetModelRelationship $event
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|null
     */
    public function getModelRelationship(GetModelRelationship $event)
    {
        if ($event->isRelationship(Post::class, 'mentionedBy')) {
            return $event->model->belongsToMany(Post::class, 'mentions_posts', 'mentions_id', 'post_id', 'mentionedBy');
        }

        if ($event->isRelationship(Post::class, 'mentionsPosts')) {
            return $event->model->belongsToMany(Post::class, 'mentions_posts', 'post_id', 'mentions_id', 'mentionsPosts');
        }

        if ($event->isRelationship(Post::class, 'mentionsUsers')) {
            return $event->model->belongsToMany(User::class, 'mentions_users', 'post_id', 'mentions_id', 'mentionsUsers');
        }
    }

    /**
     * @param GetApiRelationship $event
     * @return \Tobscure\JsonApi\Relationship|null
     */
    public function getApiRelationship(GetApiRelationship $event)
    {
        if ($event->isRelationship(PostBasicSerializer::class, 'mentionedBy')) {
            return $event->serializer->hasMany($event->model, PostBasicSerializer::class, 'mentionedBy');
        }

        if ($event->isRelationship(PostBasicSerializer::class, 'mentionsPosts')) {
            return $event->serializer->hasMany($event->model, PostBasicSerializer::class, 'mentionsPosts');
        }

        if ($event->isRelationship(PostBasicSerializer::class, 'mentionsUsers')) {
            return $event->serializer->hasMany($event->model, PostBasicSerializer::class, 'mentionsUsers');
        }
    }

    /**
     * @param ConfigureApiController $event
     */
    public function includeRelationships(ConfigureApiController $event)
    {
        if ($event->isController(ShowDiscussionController::class)) {
            $event->addInclude([
                'posts.mentionedBy',
                'posts.mentionedBy.user',
                'posts.mentionedBy.discussion'
            ]);
        }

        if ($event->isController(ShowPostController::class)
            || $event->isController(ListPostsController::class)) {
            $event->addInclude([
                'mentionedBy',
                'mentionedBy.user',
                'mentionedBy.discussion'
            ]);
        }

        if ($event->isController(CreatePostController::class)) {
            $event->addInclude([
                'mentionsPosts',
                'mentionsPosts.mentionedBy'
            ]);
        }
    }
}
