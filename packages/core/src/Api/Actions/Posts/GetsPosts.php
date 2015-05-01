<?php namespace Flarum\Api\Actions\Posts;

use Flarum\Core\Models\User;
use Flarum\Api\JsonApiRequest;

trait GetsPosts
{
	protected function getPosts(JsonApiRequest $request, array $where)
	{
        $user = $request->actor->getUser();

        if (isset($where['discussion_id']) && ($near = $request->get('near')) > 1) {
            $start = $this->posts->getIndexForNumber($where['discussion_id'], $near, $user);
            $start = max(0, $request->offset - $request->limit / 2);
        } else {
            $start = 0;
        }

        return $this->posts->findWhere(
            $where,
            $user,
            $request->sort,
            $request->limit,
            $request->offset
        );
	}
}
