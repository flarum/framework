<?php namespace Flarum\Api\Actions\Posts;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Flarum\Core\Posts\Post;
use Flarum\Api\Actions\Base;
use Flarum\Api\Serializers\PostSerializer;

class Show extends Base
{
    /**
	 * Show a single or multiple posts by ID.
     * @todo put a cap on how many can be requested
	 *
	 * @return Response
	 */
    protected function run()
    {
        $ids = $this->explodeIds($this->param('id'));
        $posts = Post::whereCanView()->whereIn('id', $ids)->get();

        if (! count($posts)) {
            throw new ModelNotFoundException;
        }

        $include = $this->included(['discussion', 'replyTo']);
        $relations = array_merge(['user', 'editUser', 'hideUser'], $include);
        $posts->load($relations);

        // Finally, we can set up the post serializer and use it to create
        // a post resource or collection, depending on how many posts were
        // requested.
        $serializer = new PostSerializer($relations);
        $this->document->setPrimaryElement(
            count($ids) == 1 ? $serializer->resource($posts->first()) : $serializer->collection($posts)
        );

        return $this->respondWithDocument();
    }
}
