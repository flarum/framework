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
        $schema->table('discussions', function (Blueprint $table) {
            $table->index('last_posted_at');
            $table->index('last_posted_user_id');
            $table->index('created_at');
            $table->index('user_id');
            $table->index('comment_count');
            $table->index('participant_count');
            $table->index('hidden_at');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('discussions', function (Blueprint $table) {
            $table->dropIndex(['last_posted_at']);
            $table->dropIndex(['last_posted_user_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['comment_count']);
            $table->dropIndex(['participant_count']);
            $table->dropIndex(['hidden_at']);
        });
    }
];
