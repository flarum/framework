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
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Expression;

class LikeCount
{
    public function prepareDeletion(DeletingPosts $event): void
    {
        $db = $event->posts->getConnection();
        $posts = (clone $event->posts)->reorder('id')->lockForUpdate()->get(['id', 'type', 'is_private']);
        $postIds = $posts->modelKeys();
        $eligibleIds = $posts->filter(fn ($post) => $post->type === 'comment' && $post->is_private === false)->modelKeys();
        $removedByUser = [];

        foreach (array_chunk($eligibleIds, 1000) as $chunk) {
            $likes = $db->table('post_likes')->whereIn('post_id', $chunk)
                ->orderBy('post_id')->orderBy('user_id')->lockForUpdate()->cursor();

            foreach ($likes as $like) {
                $removedByUser[$like->user_id] = ($removedByUser[$like->user_id] ?? 0) + 1;
            }
        }

        // Like mutations take the same locks before changing the pivot and counter.
        // Hold these through the SQL deletion as well as the counter update.
        $this->lockUsers($db, array_keys($removedByUser));

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
                        'like_count' => new Expression("CASE WHEN like_count >= $count THEN like_count - $count ELSE 0 END"),
                    ]);
                }
            }
        });
    }

    /** @param int[] $userIds */
    private function lockUsers(ConnectionInterface $db, array $userIds): void
    {
        sort($userIds);

        foreach (array_chunk($userIds, 1000) as $ids) {
            $db->table('users')->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get(['id']);
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
