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
        //
        // MySQL and MariaDB index a foreign key column already; adding another
        // index replaces that one, and down() then can't drop it (error 1553).
        if (! in_array($schema->getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $schema->table('post_likes', function (Blueprint $table) {
                $table->index('user_id');
            });
        }

        // Backfill the likes on comments that contribute to the profile tab.
        $connection = $schema->getConnection();

        $likeCount = $connection->table('post_likes')
            ->join('posts', 'posts.id', '=', 'post_likes.post_id')
            ->whereColumn('post_likes.user_id', 'users.id')
            ->where('posts.is_private', false)
            ->where('posts.type', 'comment')
            ->selectRaw('COUNT(*)');

        $connection->table('users')->select('id')->chunkById(5000, function ($users) use ($connection, $likeCount) {
            $connection->table('users')
                ->whereIn('id', $users->pluck('id')->all())
                ->update(['like_count' => $likeCount]);
        });
    },

    'down' => function (Builder $schema) {
        if (! in_array($schema->getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $schema->table('post_likes', function (Blueprint $table) {
                $table->dropIndex(['user_id']);
            });
        }

        $schema->table('users', function (Blueprint $table) {
            $table->dropColumn('like_count');
        });
    }
];
