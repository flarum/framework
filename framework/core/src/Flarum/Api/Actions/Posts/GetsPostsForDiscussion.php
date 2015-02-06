<?php namespace Flarum\Api\Actions\Posts;

trait GetsPostsForDiscussion
{
	protected function getPostsForDiscussion($repository, $discussionId, $relations = [])
	{
		$sort = $this->sort(['time']);
        $count = $this->count(20, 50);

        if (($near = $this->input('near')) > 1) {
            $start = $repository->getIndexForNumber($discussionId, $near);
            $start = max(0, $start - $count / 2);
        } else {
            $start = 0;
        }

        return $repository->findByDiscussion($discussionId, $relations, $sort['by'], $sort['order'] ?: 'asc', $count, $start);
	}
}
