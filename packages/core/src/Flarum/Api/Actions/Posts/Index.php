<?php namespace Flarum\Api\Actions\Posts;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Flarum\Core\Posts\Post;
use Flarum\Api\Actions\Base;
use Flarum\Api\Serializers\PostSerializer;

class Index extends Base
{
    /**
	 * Show posts from a discussion.
	 *
	 * @return Response
	 */
    protected function run()
    {
        $discussionId = $this->input('discussions');

        $count = $this->count(20, 50);

        if ($near = $this->input('near')) {
            // fetch the nearest post
            $post = Post::orderByRaw('ABS(CAST(number AS SIGNED) - ?)', [$near])->whereNotNull('number')->where('discussion_id', $discussionId)->take(1)->first();

            $start = max(
                0,
                Post::whereCanView()
                    ->where('discussion_id', $discussionId)
                    ->where('time', '<=', $post->time)
                    ->count() - round($count / 2)
            );
        } else {
            $start = $this->start();
        }

        $include = $this->included([]);
        $sort    = $this->sort(['time']);

        $relations = array_merge(['user', 'user.groups', 'editUser', 'deleteUser'], $include);

        // @todo move to post repository
        $posts = Post::with($relations)
            ->whereCanView()
            ->where('discussion_id', $discussionId)
            ->skip($start)
            ->take($count)
            ->orderBy($sort['by'], $sort['order'] ?: 'asc')
            ->get();

        if (! count($posts)) {
            throw new ModelNotFoundException;
        }

        // Finally, we can set up the post serializer and use it to create
        // a post resource or collection, depending on how many posts were
        // requested.
        $serializer = new PostSerializer($relations);
        $this->document->setPrimaryElement($serializer->collection($posts));

        return $this->respondWithDocument();
    }
}
