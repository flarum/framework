<?php namespace Flarum\Api\Actions\Posts;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Flarum\Core\Repositories\PostRepositoryInterface;
use Flarum\Core\Support\Actor;
use Flarum\Api\Actions\BaseAction;
use Flarum\Api\Actions\ApiParams;
use Flarum\Api\Serializers\PostSerializer;

class IndexAction extends BaseAction
{
    use GetsPosts;

    /**
     * The post repository.
     *
     * @var Post
     */
    protected $posts;

    /**
     * Instantiate the action.
     *
     * @param Post $posts
     */
    public function __construct(Actor $actor, PostRepositoryInterface $posts)
    {
        $this->actor = $actor;
        $this->posts = $posts;
    }

    /**
     * Show posts from a discussion, or by providing an array of IDs.
	 *
	 * @return Response
	 */
    protected function run(ApiParams $params)
    {
        $postIds = (array) $params->get('ids');
        $include = ['user', 'user.groups', 'editUser', 'hideUser', 'discussion'];
        $user = $this->actor->getUser();

        if (count($postIds)) {
            $posts = $this->posts->findByIds($postIds, $user);
        } else {
            if ($discussionId = $params->get('discussions')) {
                $where['discussion_id'] = $discussionId;
            }
            if ($userId = $params->get('users')) {
                $where['user_id'] = $userId;
            }
            $posts = $this->getPosts($params, $where, $user);
        }

        if (! count($posts)) {
            throw new ModelNotFoundException;
        }

        // Finally, we can set up the post serializer and use it to create
        // a post resource or collection, depending on how many posts were
        // requested.
        $serializer = new PostSerializer($include);
        $document = $this->document()->setPrimaryElement($serializer->collection($posts->load($include)));

        return $this->respondWithDocument($document);
    }
}
