<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes;

use Flarum\Post\Post;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Each pivot records whether it contributes to the counter. Changes to that
 * contribution and to users.like_count are committed together. Deletion only
 * subtracts those contributions: it never scans the likers' other posts.
 */
class LikeCount
{
    public function sync(Post $post): void
    {
        $db = $post->getConnection();
        $db->transaction(function () use ($post, $db) {
            $current = Post::query()->whereKey($post->id)->lockForUpdate()->first();
            if (! $current) {
                return;
            }
            $counted = $current->type === 'comment' && ! $current->is_private;
            $ids = $db->table('post_likes')->where('post_id', $post->id)
                ->where('is_counted', ! $counted)->orderBy('user_id')->pluck('user_id')->all();
            $this->adjust($db, array_fill_keys($ids, $counted ? 1 : -1));
            $db->table('post_likes')->where('post_id', $post->id)->update(['is_counted' => $counted]);
        });
    }

    /**
     * @param Builder<Post> $posts
     * @return array{array<int, int>, array<int, int>}
     */
    public function capture(Builder $posts): array
    {
        $db = $posts->getConnection();
        // The caller's deletion transaction holds these locks until the model
        // and its counters have both been changed.
        $ids = (clone $posts)->orderBy('id')->lockForUpdate()->pluck('id')->all();
        $changes = [];
        foreach (array_chunk($ids, 1000) as $chunk) {
            $counts = $db->table('post_likes')->whereIn('post_id', $chunk)
                ->where('is_counted', true)->selectRaw('user_id, COUNT(*) AS total')->groupBy('user_id')->get();
            foreach ($counts as $count) {
                $changes[$count->user_id] = ($changes[$count->user_id] ?? 0) - (int) $count->total;
            }
        }

        return [$ids, $changes];
    }

    /** @param array{array<int, int>, array<int, int>} $captured */
    public function removed(ConnectionInterface $db, array $captured): void
    {
        [$ids, $changes] = $captured;
        // Also clear pivots on installations without cascading foreign keys.
        foreach (array_chunk($ids, 1000) as $chunk) {
            $db->table('post_likes')->whereIn('post_id', $chunk)->delete();
        }
        $this->adjust($db, $changes);
    }

    /** @param Builder<Post> $posts */
    public function remove(Builder $posts): void
    {
        $db = $posts->getConnection();
        $db->transaction(function () use ($posts, $db) {
            $this->removed($db, $this->capture($posts));
        });
    }

    /** @param array<int, int> $changes */
    public function adjust(ConnectionInterface $db, array $changes): void
    {
        if (! $changes) {
            return;
        }
        ksort($changes);
        // A consistent lock order also serializes like, unlike, approval and
        // overlapping bulk deletions affecting the same users.
        foreach (array_chunk(array_keys($changes), 1000) as $ids) {
            $db->table('users')->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get(['id']);
        }
        $groups = [];
        foreach ($changes as $id => $delta) {
            $groups[$delta][] = $id;
        }
        foreach ($groups as $delta => $ids) {
            foreach (array_chunk($ids, 1000) as $chunk) {
                $db->table('users')->whereIn('id', $chunk)->increment('like_count', (int) $delta);
            }
        }
    }
}
