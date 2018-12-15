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

use Flarum\Api\Controller;
use Flarum\Api\Event\WillGetData;
use Flarum\Api\Serializer\BasicPostSerializer;
use Flarum\Event\GetApiRelationship;
use Flarum\Event\GetModelRelationship;
use Flarum\Post\Post;
use Flarum\User\User;
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
        $events->listen(WillGetData::class, [$this, 'includeRelationships']);
    }

    /**
     * @param GetModelRelationship $event
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|null
     */
    public function getModelRelationship(GetModelRelationship $event)
    {
        if ($event->isRelationship(Post::class, 'mentionedBy')) {
            return $event->model->belongsToMany(Post::class, 'post_mentions_post', 'mentions_post_id', 'post_id', null, null, 'mentionedBy');
        }

        if ($event->isRelationship(Post::class, 'mentionsPosts')) {
            return $event->model->belongsToMany(Post::class, 'post_mentions_post', 'post_id', 'mentions_post_id', null, null, 'mentionsPosts');
        }

        if ($event->isRelationship(Post::class, 'mentionsUsers')) {
            return $event->model->belongsToMany(User::class, 'post_mentions_user', 'post_id', 'mentions_user_id', null, null, 'mentionsUsers');
        }
    }

    /**
     * @param GetApiRelationship $event
     * @return \Tobscure\JsonApi\Relationship|null
     */
    public function getApiRelationship(GetApiRelationship $event)
    {
        if ($event->isRelationship(BasicPostSerializer::class, 'mentionedBy')) {
            return $event->serializer->hasMany($event->model, BasicPostSerializer::class, 'mentionedBy');
        }

        if ($event->isRelationship(BasicPostSerializer::class, 'mentionsPosts')) {
            return $event->serializer->hasMany($event->model, BasicPostSerializer::class, 'mentionsPosts');
        }

        if ($event->isRelationship(BasicPostSerializer::class, 'mentionsUsers')) {
            return $event->serializer->hasMany($event->model, BasicPostSerializer::class, 'mentionsUsers');
        }
    }

    /**
     * @param WillGetData $event
     */
    public function includeRelationships(WillGetData $event)
    {
        if ($event->isController(Controller\ShowDiscussionController::class)) {
            $event->addInclude([
                'posts.mentionedBy',
                'posts.mentionedBy.user',
                'posts.mentionedBy.discussion'
            ]);
        }

        if ($event->isController(Controller\ShowPostController::class)
            || $event->isController(Controller\ListPostsController::class)) {
            $event->addInclude([
                'mentionedBy',
                'mentionedBy.user',
                'mentionedBy.discussion'
            ]);
        }

        if ($event->isController(Controller\CreatePostController::class)) {
            $event->addInclude([
                'mentionsPosts',
                'mentionsPosts.mentionedBy'
            ]);
        }
    }
}
