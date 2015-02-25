<?php namespace Flarum\Api\Actions\Posts;

use Flarum\Core\Models\User;
use Flarum\Api\Actions\ApiParams;

trait GetsPostsForDiscussion
{
	protected function getPostsForDiscussion(ApiParams $params, $discussionId)
	{
		$sort = $params->sort(['time']);
        $count = $params->count(20, 50);
        $user = $this->actor->getUser();

        if (($near = $params->get('near')) > 1) {
            $start = $this->posts->getIndexForNumber($discussionId, $near, $user);
            $start = max(0, $start - $count / 2);
        } else {
            $start = 0;
        }

        return $this->posts->findByDiscussion(
            $discussionId,
            $user,
            $sort['field'],
            $sort['order'] ?: 'asc',
            $count,
            $start
        );
	}
}
