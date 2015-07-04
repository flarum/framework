<?php namespace Flarum\Core\Activity;

use Flarum\Core\Users\User;

interface ActivityRepositoryInterface
{
    /**
     * Find a user's activity.
     *
     * @param integer $userId
     * @param \Flarum\Core\Users\User $actor
     * @param null|integer $count
     * @param integer $start
     * @param null|string $type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByUser($userId, User $actor, $count = null, $start = 0, $type = null);
}
