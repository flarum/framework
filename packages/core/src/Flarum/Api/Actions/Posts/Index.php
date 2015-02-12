<?php namespace Flarum\Api\Actions\Posts;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Flarum\Core\Posts\Post;
use Flarum\Core\Posts\PostRepository;
use Flarum\Api\Actions\Base;
use Flarum\Api\Serializers\PostSerializer;

class Index extends Base
{
    use GetsPostsForDiscussion;

    /**
     * The post repository.
     *
     * @var PostRepository
     */
    protected $posts;

    /**
     * Instantiate the action.
     *
     * @param PostRepository $posts
     */
    public function __construct(PostRepository $posts)
    {
        $this->posts = $posts;
    }

    /**
     * Show posts from a discussion, or by providing an array of IDs.
	 *
	 * @return Response
	 */
    protected function run()
    {
        $postIds = (array) $this->input('ids');
        $include = ['user', 'user.groups', 'editUser', 'hideUser'];

        if (count($postIds)) {
            $posts = $this->posts->findMany($postIds, $include);
        } else {
            $discussionId = $this->input('discussions');
            $posts = $this->getPostsForDiscussion($this->posts, $discussionId, $include);
        }

        if (! count($posts)) {
            throw new ModelNotFoundException;
        }

        // Finally, we can set up the post serializer and use it to create
        // a post resource or collection, depending on how many posts were
        // requested.
        $serializer = new PostSerializer($include);
        $this->document->setPrimaryElement($serializer->collection($posts));

        return $this->respondWithDocument();
    }
}
