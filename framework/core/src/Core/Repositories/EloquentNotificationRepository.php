<?php namespace Flarum\Core\Repositories;

use Flarum\Core\Models\Notification;
use Flarum\Core\Models\User;
use DB;

class EloquentNotificationRepository implements NotificationRepositoryInterface
{
    public function findByUser(User $user, $limit = null, $offset = 0)
    {
        $primaries = Notification::select(DB::raw('MAX(id) AS id'), DB::raw('SUM(is_read = 0) AS unread_count'))
            ->where('user_id', $user->id)
            ->whereIn('type', array_filter(array_keys(Notification::getTypes()), [$user, 'shouldAlert']))
            ->where('is_deleted', false)
            ->groupBy('type', 'subject_id')
            ->orderBy('time', 'desc')
            ->skip($offset)
            ->take($limit);

        return Notification::with('subject')
            ->select('notifications.*', 'p.unread_count')
            ->mergeBindings($primaries->getQuery())
            ->join(DB::raw('('.$primaries->toSql().') p'), 'notifications.id', '=', 'p.id')
            ->orderBy('time', 'desc')
            ->get();
    }
}
