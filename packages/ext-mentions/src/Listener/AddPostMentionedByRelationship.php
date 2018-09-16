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
use Flarum\Api\Event\WillSerializeData;
use Flarum\Api\Serializer\BasicPostSerializer;
use Flarum\Event\GetApiRelationship;
use Flarum\Event\GetModelRelationship;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Flarum\Post\PostRepository;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Collection;

class AddPostMentionedByRelationship
{
    /**
     * @var PostRepository
     */
    protected $posts;

    /**
     * @param PostRepository $posts
     */
    public function __construct(PostRepository $posts)
    {
        $this->posts = $posts;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(GetModelRelationship::class, [$this, 'getModelRelationship']);
        $events->listen(GetApiRelationship::class, [$this, 'getApiRelationship']);
        $events->listen(WillGetData::class, [$this, 'includeRelationships']);
        $events->listen(WillSerializeData::class, [$this, 'filterVisiblePosts']);
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

    /**
     * Apply visibility permissions to API data.
     *
     * Each post in an API document has a relationship with posts that have
     * mentioned it (mentionedBy). This listener will manually filter these
     * additional posts so that the user can't see any posts which they don't
     * have access to.
     *
     * @param WillSerializeData $event
     */
    public function filterVisiblePosts(WillSerializeData $event)
    {
        // Firstly we gather a list of posts contained within the API document.
        // This will vary according to the API endpoint that is being accessed.
        if ($event->isController(Controller\ShowDiscussionController::class)) {
            $posts = $event->data->posts;
        } elseif ($event->isController(Controller\ShowPostController::class)
            || $event->isController(Controller\CreatePostController::class)
            || $event->isController(Controller\UpdatePostController::class)) {
            $posts = [$event->data];
        } elseif ($event->isController(Controller\ListPostsController::class)) {
            $posts = $event->data;
        }

        if (isset($posts)) {
            $posts = new Collection($posts);

            $posts = $posts->filter(function ($post) {
                return $post instanceof CommentPost;
            });

            // Load all of the users that these posts mention. This way the data
            // will be ready to go when we need to sub in current usernames
            // during the rendering process.
            $posts->load(['mentionsUsers', 'mentionsPosts.user']);

            // Construct a list of the IDs of all of the posts that these posts
            // have been mentioned in. We can then filter this list of IDs to
            // weed out all of the ones which the user is not meant to see.
            $ids = [];

            foreach ($posts as $post) {
                $ids = array_merge($ids, $post->mentionedBy->pluck('id')->all());
            }

            $ids = $this->posts->filterVisibleIds($ids, $event->actor);

            // Finally, go back through each of the posts and filter out any
            // of the posts in the relationship data that we now know are
            // invisible to the user.
            foreach ($posts as $post) {
                $post->setRelation('mentionedBy', $post->mentionedBy->filter(function ($post) use ($ids) {
                    return array_search($post->id, $ids) !== false;
                }));
            }
        }
    }
}
