<?php namespace Flarum\Api\Actions\Posts;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Flarum\Core\Repositories\PostRepositoryInterface;
use Flarum\Core\Support\Actor;
use Flarum\Api\Actions\BaseAction;
use Flarum\Api\Actions\ApiParams;
use Flarum\Api\Serializers\PostSerializer;

class ShowAction extends BaseAction
{
    protected $posts;

    public function __construct(Actor $actor, PostRepositoryInterface $posts)
    {
        $this->actor = $actor;
        $this->posts = $posts;
    }

    /**
	 * Show a single post by ID.
	 *
	 * @return Response
	 */
    protected function run(ApiParams $params)
    {
        $id = $params->get('id');
        $posts = $this->posts->findOrFail($id, $this->actor->getUser());

        $include = $params->included(['discussion', 'replyTo']);
        $relations = array_merge(['user', 'editUser', 'hideUser'], $include);
        $posts->load($relations);

        // Finally, we can set up the post serializer and use it to create
        // a post resource or collection, depending on how many posts were
        // requested.
        $serializer = new PostSerializer($relations);
        $document = $this->document()->setData($serializer->resource($posts->first()));

        return $this->respondWithDocument($document);
    }
}
