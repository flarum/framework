<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions;

use Flarum\Post\Post;
use Flarum\User\User;

/**
 * Keeps users.mention_count, the number of posts that mention a user, in step
 * with the Mentions tab on their profile. Like core's comment_count, it's a
 * full recount each time, and private posts aren't counted.
 */
class UserMentionCount
{
    /**
     * @param array<int|string> $userIds
     */
    public function refresh(array $userIds): void
    {
        $userIds = array_unique(array_map('intval', $userIds));

        if (empty($userIds)) {
            return;
        }

        $mentionCount = Post::query()->getConnection()->table('post_mentions_user')
            ->join('posts', 'posts.id', '=', 'post_mentions_user.post_id')
            ->whereColumn('post_mentions_user.mentions_user_id', 'users.id')
            ->where('posts.is_private', false)
            ->selectRaw('COUNT(*)');

        User::query()->getConnection()->table('users')
            ->whereIn('id', $userIds)
            ->update(['mention_count' => $mentionCount]);
    }
}
