<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions;

use Flarum\Api\Controller;
use Flarum\Http\RequestUtil;
use Flarum\Post\CommentPost;
use Flarum\Post\PostRepository;
use Illuminate\Database\Eloquent\Collection;
use Psr\Http\Message\ServerRequestInterface;

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
     * @param Controller\AbstractSerializeController $controller
     * @param mixed $data
     */
    public function __invoke(Controller\AbstractSerializeController $controller, $data, ServerRequestInterface $request)
    {
        // Firstly we gather a list of posts contained within the API document.
        // This will vary according to the API endpoint that is being accessed.
        if ($controller instanceof Controller\ShowDiscussionController) {
            $posts = $data->posts;
        } elseif ($controller instanceof Controller\ShowPostController
            || $controller instanceof Controller\CreatePostController
            || $controller instanceof Controller\UpdatePostController) {
            $posts = [$data];
        } elseif ($controller instanceof Controller\ListPostsController) {
            $posts = $data;
        }

        if (isset($posts)) {
            $posts = new Collection($posts);
            $actor = RequestUtil::getActor($request);

            $posts = $posts->filter(function ($post) {
                return $post instanceof CommentPost;
            });

            // Load all of the users that these posts mention. This way the data
            // will be ready to go when we need to sub in current usernames
            // during the rendering process.
            $posts->loadMissing(['mentionsUsers', 'mentionsPosts.user', 'mentionedBy']);

            // Construct a list of the IDs of all of the posts that these posts
            // have been mentioned in. We can then filter this list of IDs to
            // weed out all of the ones which the user is not meant to see.
            $ids = [];

            foreach ($posts as $post) {
                $ids = array_merge($ids, $post->mentionedBy->pluck('id')->all());
            }

            $ids = $this->posts->filterVisibleIds($ids, $actor);

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
