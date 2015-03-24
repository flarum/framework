<?php namespace Flarum\Core\Repositories;

interface NotificationRepositoryInterface
{
    public function findByUser($userId, $count = null, $start = 0);
}
