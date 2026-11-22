<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes;

use Flarum\Post\Event\DeletingPosts;
use Flarum\User\User;
use Illuminate\Database\Query\Expression;

class LikeCount
{
    public function prepareDeletion(DeletingPosts $event): void
    {
        $db = $event->connection;
        $postIds = $event->postIds;
        $removedByUser = [];

        // Core holds the post locks. Group in SQL so only counts come back.
        foreach (array_chunk($postIds, 1000) as $chunk) {
            $counts = $db->table('post_likes')
                ->join('posts', 'posts.id', '=', 'post_likes.post_id')
                ->whereIn('post_likes.post_id', $chunk)
                ->where('posts.type', 'comment')
                ->where('posts.is_private', false)
                ->select('post_likes.user_id')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('post_likes.user_id')
                ->pluck('total', 'post_likes.user_id');

            foreach ($counts as $id => $count) {
                $removedByUser[$id] = ($removedByUser[$id] ?? 0) + (int) $count;
            }
        }

        // Like mutations take the same locks before changing the pivot and counter.
        // Hold these through the SQL deletion as well as the counter update.
        $event->lockUsers(array_keys($removedByUser));

        $event->afterDeletion(function () use ($db, $postIds, $removedByUser) {
            // SQLite installs without cascading foreign keys still need cleanup.
            foreach (array_chunk($postIds, 1000) as $ids) {
                $db->table('post_likes')->whereIn('post_id', $ids)->delete();
            }

            $usersByCount = [];
            foreach ($removedByUser as $id => $count) {
                $usersByCount[$count][] = $id;
            }

            foreach ($usersByCount as $count => $userIds) {
                foreach (array_chunk($userIds, 1000) as $ids) {
                    $db->table('users')->whereIn('id', $ids)->update([
                        'like_count' => self::delta(-(int) $count),
                    ]);
                }
            }
        });
    }

    /**
     * Move the stored counts of a post's likers by the change in whether the
     * post counts.
     *
     * A post counts only while it is a visible comment, so a visibility change
     * moves each of its likers by exactly one. Recounting every post they
     * liked is repair work, not something one approval should do, so this is a
     * single set-based update per chunk.
     */
    public function adjustPostLikers(int $postId, int $delta): void
    {
        if ($delta === 0) {
            return;
        }

        $db = User::query()->getConnection();

        $db->transaction(function () use ($db, $postId, $delta) {
            $userIds = $db->table('post_likes')->where('post_id', $postId)
                ->orderBy('user_id')->pluck('user_id')->all();

            foreach (array_chunk($userIds, 1000) as $ids) {
                // Same order as the like mutation and deletion: the users in
                // ascending order before their counters change.
                $db->table('users')->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get(['id']);
                $db->table('users')->whereIn('id', $ids)->update(['like_count' => self::delta($delta)]);
            }
        });
    }

    public function refreshAll(): void
    {
        User::query()->getConnection()->table('users')->select('id')
            ->chunkById(1000, fn ($users) => $this->refresh($users->pluck('id')->all()));
    }

    /**
     * Recount stored counts from the pivot, for repair (for instance when the
     * extension was enabled again after likes were deleted). Mutations must
     * move the stored count by one instead: a recount cannot be combined with
     * a concurrent like, because it would overwrite that like.
     *
     * @param int[] $userIds
     */
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

    /** SQL for moving a stored count, without taking a counter below zero. */
    private static function delta(int $delta): Expression
    {
        if ($delta > 0) {
            return new Expression("like_count + $delta");
        }

        $count = -$delta;

        return new Expression("CASE WHEN like_count >= $count THEN like_count - $count ELSE 0 END");
    }
}
