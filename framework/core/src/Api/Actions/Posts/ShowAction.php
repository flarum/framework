<?php namespace Flarum\Api\Actions\Posts;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Flarum\Core\Posts\PostRepository;
use Flarum\Api\Actions\SerializeResourceAction;
use Flarum\Api\JsonApiRequest;
use Tobscure\JsonApi\Document;

class ShowAction extends SerializeResourceAction
{
    /**
     * @var PostRepository
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
     * @param PostRepository $posts
     */
    public function __construct(PostRepository $posts)
    {
        $this->posts = $posts;
    }

    /**
     * Get a single post, ready to be serialized and assigned to the JsonApi
     * response.
     *
     * @param JsonApiRequest $request
     * @param Document $document
     * @return \Flarum\Core\Posts\Post
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        return $this->posts->findOrFail($request->get('id'), $request->actor);
    }
}
