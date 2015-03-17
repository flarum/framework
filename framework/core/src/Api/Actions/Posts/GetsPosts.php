<?php namespace Flarum\Api\Actions\Posts;

use Flarum\Core\Models\User;
use Flarum\Api\Actions\ApiParams;

trait GetsPosts
{
	protected function getPosts(ApiParams $params, $where)
	{
		$sort = $params->sort(['time']);
        $count = $params->count(20, 50);
        $user = $this->actor->getUser();

        if (isset($where['discussion_id']) && ($near = $params->get('near')) > 1) {
            $start = $this->posts->getIndexForNumber($where['discussion_id'], $near, $user);
            $start = max(0, $start - $count / 2);
        } else {
            $start = 0;
        }

        return $this->posts->findWhere(
            $where,
            $user,
            $sort['field'],
            $sort['order'] ?: 'asc',
            $count,
            $start
        );
	}
}
