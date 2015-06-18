<?php namespace Flarum\Api\Actions\Posts;

use Flarum\Core\Repositories\PostRepositoryInterface;
use Flarum\Api\Actions\SerializeCollectionAction;
use Flarum\Api\JsonApiRequest;
use Tobscure\JsonApi\Document;

class IndexAction extends SerializeCollectionAction
{
    use GetsPosts;

    /**
     * @var \Flarum\Core\Repositories\PostRepositoryInterface
     */
    protected $posts;

    /**
     * @inheritdoc
     */
    public static $serializer = 'Flarum\Api\Serializers\PostSerializer';

    /**
     * @inheritdoc
     */
    public static $include = [
        'user' => true,
        'user.groups' => true,
        'editUser' => true,
        'hideUser' => true,
        'discussion' => true
    ];

    /**
     * @inheritdoc
     */
    public static $link = [];

    /**
     * @inheritdoc
     */
    public static $limitMax = 50;

    /**
     * @inheritdoc
     */
    public static $limit = 20;

    /**
     * @inheritdoc
     */
    public static $sortFields = [];

    /**
     * @inheritdoc
     */
    public static $sort;

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
     * @param \Tobscure\JsonApi\Document $document
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        $postIds = (array) $request->get('ids');
        $user = $request->actor->getUser();

        if (count($postIds)) {
            $posts = $this->posts->findByIds($postIds, $user);
        } else {
            $where = [];
            if ($discussionId = $request->get('discussions')) {
                $where['discussion_id'] = $discussionId;
            }
            if ($number = $request->get('number')) {
                $where['number'] = $number;
            }
            if ($userId = $request->get('users')) {
                $where['user_id'] = $userId;
            }
            $posts = $this->getPosts($request, $where);
        }

        return $posts;
    }
}
