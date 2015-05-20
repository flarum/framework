<?php namespace Flarum\Core\Repositories;

use Flarum\Core\Models\User;

interface NotificationRepositoryInterface
{
    public function findByUser(User $user, $count = null, $start = 0);
}
