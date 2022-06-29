<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes\Model;

use Flarum\Post\Post;
use Flarum\User\User;

class RecentLikesRelationship
{
    public function __invoke(Post $post)
    {
        return $post->belongsToMany(
            User::class,
            'post_likes',
            'post_id',
            'user_id'
        )
            ->limit(3)
            ->orderBy('post_likes.created_at');
    }
}
