<?php namespace Flarum\Core\Repositories;

use Flarum\Core\Models\Activity;
use Flarum\Core\Models\Post;
use Flarum\Core\Models\User;

class EloquentActivityRepository implements ActivityRepositoryInterface
{
    public function findByUser($userId, User $viewer, $count = null, $start = 0, $type = null)
    {
        // This is all very rough and needs to be cleaned up

        $null = \DB::raw('NULL');
        $query = Activity::with('sender')->select('id', 'user_id', 'sender_id', 'type', 'data', 'time', \DB::raw('NULL as post_id'))->where('user_id', $userId);

        if ($type) {
            $query->where('type', $type);
        }

        $posts = Post::whereCan($viewer, 'view')->with('post', 'post.discussion', 'post.user', 'post.discussion.startUser', 'post.discussion.lastUser')->select(\DB::raw("CONCAT('post', id)"), 'user_id', $null, \DB::raw("'post'"), $null, 'time', 'id')->where('user_id', $userId)->where('type', 'comment');

        if ($type === 'post') {
            $posts->where('number', '>', 1);
        } elseif ($type === 'discussion') {
            $posts->where('number', 1);
        }

        if (!$type) {
            $join = User::select(\DB::raw("CONCAT('join', id)"), 'id', 'id', \DB::raw("'join'"), $null, 'join_time', $null)->where('id', $userId);
            $query->union($join->getQuery());
        }

        return $query->union($posts->getQuery())
            ->orderBy('time', 'desc')
            ->skip($start)
            ->take($count)
            ->get();
    }
}
