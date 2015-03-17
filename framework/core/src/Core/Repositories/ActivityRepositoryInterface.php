<?php namespace Flarum\Core\Repositories;

use Flarum\Core\Models\User;

interface ActivityRepositoryInterface
{
    public function findByUser($userId, User $user, $count = null, $start = 0, $type = null);
}
