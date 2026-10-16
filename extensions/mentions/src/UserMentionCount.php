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

        $counts = Post::query()
            ->join('post_mentions_user', 'posts.id', '=', 'post_mentions_user.post_id')
            ->whereIn('post_mentions_user.mentions_user_id', $userIds)
            ->where('posts.is_private', false)
            ->groupBy('post_mentions_user.mentions_user_id')
            ->selectRaw('COUNT(*) as aggregate')
            ->addSelect('post_mentions_user.mentions_user_id')
            ->toBase()
            ->pluck('aggregate', 'mentions_user_id');

        foreach (User::query()->whereIn('id', $userIds)->get() as $user) {
            $user->mention_count = (int) ($counts[$user->id] ?? 0);
            $user->save();
        }
    }
}
