<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification;

use Carbon\Carbon;
use Flarum\User\User;
use Illuminate\Database\Query\Expression;

class NotificationRepository
{
    /**
     * Find a user's notifications.
     *
     * @param User $user
     * @param int|null $limit
     * @param int $offset
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByUser(User $user, $limit = null, $offset = 0)
    {
        $primaries = Notification::selectRaw('MAX(id) AS id')
            ->selectRaw('SUM(read_at IS NULL) AS unread_count')
            ->where('user_id', $user->id)
            ->whereIn('type', $user->getAlertableNotificationTypes())
            ->where('is_deleted', false)
            ->whereSubjectVisibleTo($user)
            ->groupBy('type', 'subject_id')
            ->orderByRaw('MAX(created_at) DESC')
            ->skip($offset)
            ->take($limit);

        return Notification::select('notifications.*')
            ->selectRaw('p.unread_count')
            // Expression is necessary until Laravel 5.8.
            // See https://github.com/laravel/framework/pull/28400
            ->joinSub($primaries, 'p', 'notifications.id', '=', new Expression('p.id'))
            ->latest()
            ->get();
    }

    /**
     * Mark all of a user's notifications as read.
     *
     * @param User $user
     *
     * @return void
     */
    public function markAllAsRead(User $user)
    {
        Notification::where('user_id', $user->id)->update(['read_at' => Carbon::now()]);
    }
}
