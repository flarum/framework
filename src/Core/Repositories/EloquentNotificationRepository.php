<?php namespace Flarum\Core\Repositories;

use Flarum\Core\Models\Notification;
use DB;

class EloquentNotificationRepository implements NotificationRepositoryInterface
{
    public function findByUser($userId, $limit = null, $offset = 0)
    {
        $primaries = Notification::select(DB::raw('MAX(id) AS id'), DB::raw('SUM(is_read = 0) AS unread_count'))
            ->where('user_id', $userId)
            ->whereIn('type', array_keys(Notification::getTypes()))
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
