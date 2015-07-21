<?php namespace Flarum\Api\Actions\Posts;

use Flarum\Core\Posts\PostRepository;
use Flarum\Api\Actions\SerializeCollectionAction;
use Flarum\Api\JsonApiRequest;
use Tobscure\JsonApi\Document;

class IndexAction extends SerializeCollectionAction
{
    use GetsPosts;

    /**
     * @inheritdoc
     */
    public $serializer = 'Flarum\Api\Serializers\PostSerializer';

    /**
     * @inheritdoc
     */
    public $include = [
        'user' => true,
        'user.groups' => true,
        'editUser' => true,
        'hideUser' => true,
        'discussion' => true
    ];

    /**
     * @inheritdoc
     */
    public $link = [];

    /**
     * @inheritdoc
     */
    public $limitMax = 50;

    /**
     * @inheritdoc
     */
    public $limit = 20;

    /**
     * @inheritdoc
     */
    public $sortFields = [];

    /**
     * @inheritdoc
     */
    public $sort;

    /**
     * @param PostRepository $posts
     */
    public function __construct(PostRepository $posts)
    {
        $this->posts = $posts;
    }

    /**
     * Get the post results, ready to be serialized and assigned to the
     * document response.
     *
     * @param JsonApiRequest $request
     * @param Document $document
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        $postIds = (array) $request->get('ids');
        $actor = $request->actor;

        if (count($postIds)) {
            $posts = $this->posts->findByIds($postIds, $actor);
        } else {
            $where = [];
            if ($discussionId = $request->get('filter.discussion')) {
                $where['discussion_id'] = $discussionId;
            }
            if ($number = $request->get('page.number')) {
                $where['number'] = $number;
            }
            if ($userId = $request->get('filter.user')) {
                $where['user_id'] = $userId;
            }
            $posts = $this->getPosts($request, $where);
        }

        return $posts;
    }
}
