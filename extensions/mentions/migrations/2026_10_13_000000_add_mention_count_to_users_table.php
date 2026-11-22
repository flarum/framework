<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Mentions\UserMentionCount;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('users', function (Blueprint $table) {
            $table->unsignedInteger('mention_count')->default(0);
        });

        // The count is refreshed by mentions_user_id. The primary key leads
        // with post_id, so it can't serve that lookup.
        //
        // MySQL and MariaDB index a foreign key column already; adding another
        // index replaces that one, and down() then can't drop it (error 1553).
        if (! in_array($schema->getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $schema->table('post_mentions_user', function (Blueprint $table) {
                $table->index('mentions_user_id');
            });
        }

        // Backfill: the same count Flarum\Mentions\UserMentionCount keeps up to
        // date from now on, as a subquery. Write one UPDATE per batch of users
        // that exist, rather than one per range of ids, so a forum whose ids
        // have gaps does not run an UPDATE for every empty range.
        $connection = $schema->getConnection();

        $mentionCount = UserMentionCount::countedPosts(
            $connection->table('post_mentions_user')
                ->join('posts', 'posts.id', '=', 'post_mentions_user.post_id')
                ->whereColumn('post_mentions_user.mentions_user_id', 'users.id')
        )->selectRaw('COUNT(*)');

        $connection->table('users')->select('id')->chunkById(1000, function ($users) use ($connection, $mentionCount) {
            $connection->table('users')
                ->whereIn('id', $users->pluck('id')->all())
                ->update(['mention_count' => $mentionCount]);
        });
    },

    'down' => function (Builder $schema) {
        if (! in_array($schema->getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $schema->table('post_mentions_user', function (Blueprint $table) {
                $table->dropIndex(['mentions_user_id']);
            });
        }

        $schema->table('users', function (Blueprint $table) {
            $table->dropColumn('mention_count');
        });
    }
];
