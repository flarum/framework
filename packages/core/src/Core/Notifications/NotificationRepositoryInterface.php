<?php namespace Flarum\Core\Notifications;

use Flarum\Core\Users\User;

interface NotificationRepositoryInterface
{
    /**
     * Find a user's notifications.
     *
     * @param User $user
     * @param int|null $count
     * @param int $start
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByUser(User $user, $count = null, $start = 0);
}
