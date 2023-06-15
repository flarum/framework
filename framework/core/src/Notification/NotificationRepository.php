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
use Illuminate\Database\Eloquent\Collection;

class NotificationRepository
{
    /**
     * @return Collection<int, Notification>
     */
    public function findByUser(User $user, ?int $limit = null, int $offset = 0): Collection
    {
        $primaries = Notification::query()
            ->selectRaw('MAX(id) AS id')
            ->selectRaw('SUM(read_at IS NULL) AS unread_count')
            ->where('user_id', $user->id)
            ->whereIn('type', $user->getAlertableNotificationTypes())
            ->where('is_deleted', false)
            ->whereSubjectVisibleTo($user)
            ->groupBy('type', 'subject_id')
            ->orderByRaw('MAX(created_at) DESC')
            ->skip($offset)
            ->take($limit);

        return Notification::query()
            ->select('notifications.*', 'p.unread_count')
            ->joinSub($primaries, 'p', 'notifications.id', '=', 'p.id')
            ->latest()
            ->get();
    }

    public function markAllAsRead(User $user): void
    {
        Notification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);
    }

    public function deleteAll(User $user): void
    {
        Notification::query()->where('user_id', $user->id)->delete();
    }
}
