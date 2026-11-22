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
        $posts = $event->posts();
        $removedByUser = [];

        // Only likes on visible comments count, the same posts that the
        // profile count shows.
        $counts = $db->table('post_likes')
            ->join('posts', 'posts.id', '=', 'post_likes.post_id')
            ->whereIn('post_likes.post_id', $posts)
            ->where('posts.type', 'comment')
            ->where('posts.is_private', false)
            ->select('post_likes.user_id')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('post_likes.user_id')
            ->pluck('total', 'post_likes.user_id');

        foreach ($counts as $id => $count) {
            $removedByUser[(int) $id] = (int) $count;
        }

        // Like mutations take the same locks before changing the pivot and counter.
        // Hold these through the SQL deletion as well as the counter update.
        $event->lockUsers(array_keys($removedByUser));

        // The pivot rows for the deleted posts are removed by the cascading
        // foreign key on post_likes.post_id. Every supported database enforces
        // it: MySQL and PostgreSQL have had it since 2018, and SQLite forums
        // upgraded from 1.x get it from
        // 2026_11_22_000000_add_sqlite_post_likes_foreign_keys, because the
        // 2018 ALTER compiled to nothing there.
        $event->afterDeletion(function () use ($db, $removedByUser) {
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
     * Must be called from the visibility transaction that holds the discussion
     * lock (the `updated` listener on the post), so the pivot cannot change
     * between reading the likers and moving their counts.
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

    /**
     * Recount every stored count from the pivot, for repair (for instance when
     * the extension was enabled again after likes were deleted).
     *
     * The repair only writes rows whose stored count differs, so a forum whose
     * counts are already right writes nothing, and an interrupted repair can
     * simply run again: the next run finds the rows that are still stale.
     *
     * @return int the number of users whose count was corrected
     */
    public function refreshAll(): int
    {
        $repaired = 0;

        User::query()->getConnection()->table('users')->select('id')
            ->chunkById(1000, function ($users) use (&$repaired) {
                $repaired += $this->refresh(array_map('intval', $users->pluck('id')->all()));
            });

        return $repaired;
    }

    /**
     * Recount the stored counts of the given users from the pivot.
     *
     * Mutations must move the stored count by one instead: a recount cannot be
     * combined with a concurrent like, because it would overwrite that like.
     * So the stale users are locked in the same order as a like mutation
     * before their counters change.
     *
     * @param int[] $userIds
     * @return int the number of users whose count was corrected
     */
    private function refresh(array $userIds): int
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        sort($userIds);
        $db = User::query()->getConnection();
        $count = $db->table('post_likes')
            ->join('posts', 'posts.id', '=', 'post_likes.post_id')
            ->whereColumn('post_likes.user_id', 'users.id')
            ->where('posts.type', 'comment')
            ->where('posts.is_private', false)
            ->selectRaw('COUNT(*)');
        $repaired = 0;

        foreach (array_chunk($userIds, 1000) as $ids) {
            // The stale rows are found without locks, so a user whose count is
            // right is never locked or written. A count can only be stale here
            // if the row's value differs from the pivot, and the update below
            // rechecks it after the locks are held.
            $stale = array_map('intval', $db->table('users')->whereIn('id', $ids)
                ->where('like_count', '<>', $count)
                ->pluck('id')
                ->all());

            if ($stale === []) {
                continue;
            }

            sort($stale);

            $repaired += $db->transaction(function () use ($db, $stale, $count) {
                // Same order as the like mutation and deletion: the users in
                // ascending order before their counters change.
                $db->table('users')->whereIn('id', $stale)->orderBy('id')->lockForUpdate()->get(['id']);

                // One statement for the chunk's stale users. The count is a
                // grouped pivot lookup joined to the stored value, the same
                // shape as the backfill migration, and the guard rechecks it
                // under the lock.
                return $db->table('users')
                    ->whereIn('id', $stale)
                    ->where('like_count', '<>', $count)
                    ->update(['like_count' => $count]);
            });
        }

        return $repaired;
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
