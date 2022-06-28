<?php

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
            ->withCount([
                'likes'
            ])
            ->limit(3)
            ->orderBy('post_likes.created_at');
    }
}
