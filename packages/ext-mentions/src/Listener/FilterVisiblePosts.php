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
use Flarum\Api\Event\WillSerializeData;
use Flarum\Post\CommentPost;
use Flarum\Post\PostRepository;
use Illuminate\Database\Eloquent\Collection;

class FilterVisiblePosts
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
     * Apply visibility permissions to API data.
     *
     * Each post in an API document has a relationship with posts that have
     * mentioned it (mentionedBy). This listener will manually filter these
     * additional posts so that the user can't see any posts which they don't
     * have access to.
     *
     * @param WillSerializeData $event
     */
    public function handle(WillSerializeData $event)
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
