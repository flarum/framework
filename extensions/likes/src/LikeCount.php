<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes;

use Flarum\Post\Post;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class LikeCount
{
    /**
     * @param Builder<Post> $posts
     * @return int[]
     */
    public function affectedUserIds(Builder $posts): array
    {
        return Post::query()->getConnection()->table('post_likes')
            ->whereIn('post_id', (clone $posts)->select('id')->toBase())
            ->distinct()->pluck('user_id')->all();
    }

    /** @param int[] $userIds */
    public function decrement(array $userIds): void
    {
        $userIds = array_values(array_unique($userIds));
        sort($userIds);
        $db = User::query()->getConnection();

        foreach (array_chunk($userIds, 1000) as $ids) {
            $db->transaction(function () use ($db, $ids) {
                $db->table('users')->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get(['id']);
                $db->table('users')->whereIn('id', $ids)->where('like_count', '>', 0)->decrement('like_count');
            });
        }
    }

    /** @param int[] $userIds */
    public function refresh(array $userIds): void
    {
        $userIds = array_values(array_unique($userIds));
        sort($userIds);
        $db = User::query()->getConnection();
        $count = $db->table('post_likes')
            ->join('posts', 'posts.id', '=', 'post_likes.post_id')
            ->whereColumn('post_likes.user_id', 'users.id')
            ->where('posts.type', 'comment')
            ->where('posts.is_private', false)
            ->selectRaw('COUNT(*)');

        foreach (array_chunk($userIds, 1000) as $ids) {
            $db->transaction(function () use ($db, $ids, $count) {
                $db->table('users')->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get(['id']);
                $db->table('users')->whereIn('id', $ids)->update(['like_count' => $count]);
            });
        }
    }
}
