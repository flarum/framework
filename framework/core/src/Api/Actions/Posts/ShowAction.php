<?php namespace Flarum\Api\Actions\Posts;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Flarum\Core\Repositories\PostRepositoryInterface;
use Flarum\Api\Actions\SerializeResourceAction;
use Flarum\Api\JsonApiRequest;
use Tobscure\JsonApi\Document;

class ShowAction extends SerializeResourceAction
{
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
        'discussion' => false
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
     * Get a single post, ready to be serialized and assigned to the JsonApi
     * response.
     *
     * @param \Flarum\Api\JsonApiRequest $request
     * @param \Tobscure\JsonApi\Document $document
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        return $this->posts->findOrFail($request->get('id'), $request->actor->getUser());
    }
}
