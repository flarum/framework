<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions;

use Flarum\User\User;
use Illuminate\Database\Query\Builder;

/**
 * Keeps users.mention_count, the number of posts that mention a user, in step
 * with the Mentions tab on their profile.
 *
 * Every write also raises users.mention_count_revision by one, in the same
 * UPDATE, so the pair read from one row says which count is newer. Responses
 * can reach the browser in any order (a profile read, a new post's mentioned
 * users, an update's affected users), and the forum keeps the count with the
 * highest revision instead of the one that arrived last.
 *
 * Like core's comment_count, it is a full recount each time. Only visible
 * comments count: a post that is private (for example one waiting for
 * approval), hidden, or not a comment (a rename and so on) is left out, even
 * when it mentions the user.
 */
class UserMentionCount
{
    /**
     * Limit a query over post_mentions_user joined to posts to the posts that
     * count towards a mention count.
     */
    public static function countedPosts(Builder $query): Builder
    {
        return $query
            ->where('posts.is_private', false)
            ->whereNull('posts.hidden_at')
            ->where('posts.type', 'comment');
    }

    /**
     * @param array<int|string> $userIds
     */
    public function refresh(array $userIds): void
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));

        if (empty($userIds)) {
            return;
        }

        $db = User::query()->getConnection();

        $mentionCount = static::countedPosts(
            $db->table('post_mentions_user')
                ->join('posts', 'posts.id', '=', 'post_mentions_user.post_id')
                ->whereColumn('post_mentions_user.mentions_user_id', 'users.id')
        )->selectRaw('COUNT(*)');

        // A popular user can be mentioned in many posts, so write the counts
        // in bounded batches: one UPDATE per batch, not one per user.
        foreach (array_chunk($userIds, 1000) as $ids) {
            $db->table('users')
                ->whereIn('id', $ids)
                ->increment('mention_count_revision', 1, ['mention_count' => $mentionCount]);
        }
    }
}
