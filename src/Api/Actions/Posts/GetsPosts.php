<?php namespace Flarum\Api\Actions\Posts;

use Flarum\Api\JsonApiRequest;

trait GetsPosts
{
    protected function getPosts(JsonApiRequest $request, array $where)
    {
        $user = $request->actor->getUser();

        if (isset($where['discussion_id']) && ($near = $request->get('near')) > 1) {
            $offset = $this->posts->getIndexForNumber($where['discussion_id'], $near, $user);
            $offset = max(0, $offset - $request->limit / 2);
        } else {
            $offset = 0;
        }

        return $this->posts->findWhere(
            $where,
            $user,
            $request->sort,
            $request->limit,
            $offset
        );
    }
}
