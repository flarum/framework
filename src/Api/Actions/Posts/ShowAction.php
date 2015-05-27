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
        'discussion' => false
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
