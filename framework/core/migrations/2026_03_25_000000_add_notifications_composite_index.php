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
        $schema->table('notifications', function (Blueprint $table) {
            // Composite index to support the unread/new notification count queries
            // issued on every page load. Without this, MySQL abandons the single-
            // column user_id index when one user owns a large fraction of the table
            // (common in active communities) and falls back to a full table scan.
            //
            // Column order rationale:
            //   1. user_id   — equality prefix, mandatory filter on all count queries
            //   2. is_deleted — equality (always 0 for visible notifications)
            //   3. read_at   — IS NULL filter for unread; narrows to a small fraction
            //   4. type      — IN (...) filter; included so the index covers the query
            //
            // With this index, the count query can be satisfied with an index range
            // scan on (user_id, is_deleted=0, read_at IS NULL) without touching the
            // table rows, regardless of how many notifications a user has in total.
            $table->index(['user_id', 'is_deleted', 'read_at', 'type'], 'notifications_user_unread_type_index');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_user_unread_type_index');
        });
    }
];
