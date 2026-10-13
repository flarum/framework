<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */


use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('users', function (Blueprint $table) {
            $table->unsignedInteger('like_count')->default(0);
        });

        // The count is refreshed for one user at a time, by user_id. The primary
        // key leads with post_id, so it can't serve that lookup.
        $schema->table('post_likes', function (Blueprint $table) {
            $table->index('user_id');
        });

        // Backfill: the same count Flarum\Likes\Listener\UpdateUserLikeCount
        // keeps up to date from now on.
        $connection = $schema->getConnection();

        $connection->table('post_likes')
            ->join('posts', 'posts.id', '=', 'post_likes.post_id')
            ->where('posts.is_private', false)
            ->select('post_likes.user_id')
            ->selectRaw('COUNT(*) as like_count')
            ->groupBy('post_likes.user_id')
            ->orderBy('post_likes.user_id')
            ->chunk(1000, function ($rows) use ($connection) {
                foreach ($rows as $row) {
                    $connection->table('users')
                        ->where('id', $row->user_id)
                        ->update(['like_count' => $row->like_count]);
                }
            });
    },

    'down' => function (Builder $schema) {
        $schema->table('post_likes', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        $schema->table('users', function (Blueprint $table) {
            $table->dropColumn('like_count');
        });
    }
];
