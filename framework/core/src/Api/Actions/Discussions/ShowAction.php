<?php namespace Flarum\Api\Actions\Discussions;

use Flarum\Core\Support\Actor;
use Flarum\Core\Repositories\DiscussionRepositoryInterface as DiscussionRepository;
use Flarum\Core\Repositories\PostRepositoryInterface as PostRepository;
use Flarum\Api\Actions\BaseAction;
use Flarum\Api\Actions\ApiParams;
use Flarum\Api\Actions\Posts\GetsPosts;
use Flarum\Api\Serializers\DiscussionSerializer;

class ShowAction extends BaseAction
{
    use GetsPosts;

    /**
     * The discussion repository.
     *
     * @var DiscussionRepository
     */
    protected $discussions;

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
    public function __construct(Actor $actor, DiscussionRepository $discussions, PostRepository $posts)
    {
        $this->actor = $actor;
        $this->discussions = $discussions;
        $this->posts = $posts;
    }

    /**
     * Show a single discussion.
     *
     * @return Response
     */
    protected function run(ApiParams $params)
    {
        $include = $params->included(['startPost', 'lastPost', 'posts']);

        $discussion = $this->discussions->findOrFail($params->get('id'), $this->actor->getUser());

        $discussion->posts_ids = $discussion->posts()->get(['id'])->fetch('id')->all();

        if (in_array('posts', $include)) {
            $relations = ['user', 'user.groups', 'editUser', 'hideUser'];
            $discussion->posts = $this->getPosts($params, ['discussion_id' => $discussion->id])->load($relations);

            $include = array_merge($include, array_map(function ($relation) {
                return 'posts.'.$relation;
            }, $relations));
        }

        // Set up the discussion serializer, which we will use to create the
        // document's primary resource. As well as including the requested
        // relations, we will specify that we want the 'posts' relation to be
        // linked so that a list of post IDs will show up in the response.
        $serializer = new DiscussionSerializer($include, ['posts']);
        $document = $this->document()->setData($serializer->resource($discussion));

        return $this->respondWithDocument($document);
    }
}
