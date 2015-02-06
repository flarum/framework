<?php namespace Flarum\Api\Actions\Discussions;

use Flarum\Core\Discussions\Discussion;
use Flarum\Core\Posts\PostRepository;
use Flarum\Api\Actions\Base;
use Flarum\Api\Actions\Posts\GetsPostsForDiscussion;
use Flarum\Api\Serializers\DiscussionSerializer;

class Show extends Base
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
     * Show a single discussion.
     *
     * @return Response
     */
    protected function run()
    {
        $include = $this->included(['startPost', 'lastPost', 'posts']);

        $discussion = Discussion::whereCanView()->findOrFail($this->param('id'));

        if (in_array('posts', $include)) {
            $relations = ['user', 'user.groups', 'editUser', 'deleteUser'];
            $discussion->posts = $this->getPostsForDiscussion($this->posts, $discussion->id, $relations);

            $include = array_merge($include, array_map(function ($relation) {
                return 'posts.'.$relation;
            }, $relations));
        }

        // Set up the discussion serializer, which we will use to create the
        // document's primary resource. As well as including the requested
        // relations, we will specify that we want the 'posts' relation to be
        // linked so that a list of post IDs will show up in the response.
        $serializer = new DiscussionSerializer($include, ['posts']);
        $this->document->setPrimaryElement($serializer->resource($discussion));

        return $this->respondWithDocument();
    }
}
