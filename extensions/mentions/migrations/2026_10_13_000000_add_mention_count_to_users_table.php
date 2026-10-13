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
            $table->unsignedInteger('mention_count')->default(0);
        });

        // The count is refreshed by mentions_user_id. The primary key leads
        // with post_id, so it can't serve that lookup.
        $schema->table('post_mentions_user', function (Blueprint $table) {
            $table->index('mentions_user_id');
        });

        // Backfill: the same count Flarum\Mentions\UserMentionCount keeps up to
        // date from now on.
        $connection = $schema->getConnection();

        $connection->table('post_mentions_user')
            ->join('posts', 'posts.id', '=', 'post_mentions_user.post_id')
            ->where('posts.is_private', false)
            ->select('post_mentions_user.mentions_user_id')
            ->selectRaw('COUNT(*) as mention_count')
            ->groupBy('post_mentions_user.mentions_user_id')
            ->orderBy('post_mentions_user.mentions_user_id')
            ->chunk(1000, function ($rows) use ($connection) {
                foreach ($rows as $row) {
                    $connection->table('users')
                        ->where('id', $row->mentions_user_id)
                        ->update(['mention_count' => $row->mention_count]);
                }
            });
    },

    'down' => function (Builder $schema) {
        $schema->table('post_mentions_user', function (Blueprint $table) {
            $table->dropIndex(['mentions_user_id']);
        });

        $schema->table('users', function (Blueprint $table) {
            $table->dropColumn('mention_count');
        });
    }
];
