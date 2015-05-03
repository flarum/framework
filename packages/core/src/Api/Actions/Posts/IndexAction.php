<?php namespace Flarum\Api\Actions\Posts;

use Flarum\Core\Repositories\PostRepositoryInterface;
use Flarum\Api\Actions\SerializeCollectionAction;
use Flarum\Api\JsonApiRequest;
use Flarum\Api\JsonApiResponse;

class IndexAction extends SerializeCollectionAction
{
    use GetsPosts;

    /**
     * @var \Flarum\Core\Repositories\PostRepositoryInterface
     */
    protected $posts;

    /**
     * The name of the serializer class to output results with.
     *
     * @var string
     */
    public static $serializer = 'Flarum\Api\Serializers\PostSerializer';

    /**
     * The relationships that are available to be included, and which ones are
     * included by default.
     *
     * @var array
     */
    public static $include = [
        'user' => true,
        'user.groups' => true,
        'editUser' => true,
        'hideUser' => true,
        'discussion' => true
    ];

    /**
     * Instantiate the action.
     *
     * @param \Flarum\Core\Repositories\PostRepositoryInterface $posts
     */
    public function __construct(PostRepositoryInterface $posts)
    {
        $this->posts = $posts;
    }

    /**
     * Get the post results, ready to be serialized and assigned to the
     * document response.
     *
     * @param \Flarum\Api\JsonApiRequest $request
     * @param \Flarum\Api\JsonApiResponse $response
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function data(JsonApiRequest $request, JsonApiResponse $response)
    {
        $postIds = (array) $request->get('ids');
        $user = $request->actor->getUser();

        if (count($postIds)) {
            $posts = $this->posts->findByIds($postIds, $user);
        } else {
            if ($discussionId = $request->get('discussions')) {
                $where['discussion_id'] = $discussionId;
            }
            if ($userId = $request->get('users')) {
                $where['user_id'] = $userId;
            }
            $posts = $this->getPosts($request, $where);
        }

        return $posts;
    }
}
