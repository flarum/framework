<?php namespace Flarum\Api\Actions\Posts;

use Flarum\Api\JsonApiRequest;

trait GetsPosts
{
    /**
     * @var \Flarum\Core\Posts\PostRepository
     */
    protected $posts;

    /**
     * @param JsonApiRequest $request
     * @param array $where
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getPosts(JsonApiRequest $request, array $where)
    {
        $actor = $request->actor;

        if (isset($where['discussion_id']) && ($near = $request->get('page.near')) > 1) {
            $offset = $this->posts->getIndexForNumber($where['discussion_id'], $near, $actor);
            $offset = max(0, $offset - $request->limit / 2);
        } else {
            $offset = $request->offset;
        }

        return $this->posts->findWhere(
            $where,
            $actor,
            $request->sort,
            $request->limit,
            $offset
        );
    }
}
