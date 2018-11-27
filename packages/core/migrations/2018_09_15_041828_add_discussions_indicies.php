<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('discussions', function (Blueprint $table) use ($schema) {
            $table->index('last_posted_at');
            $table->index('last_posted_user_id');
            $table->index('created_at');
            $table->index('user_id');
            $table->index('comment_count');
            $table->index('participant_count');
            $table->index('hidden_at');

            Migration::fixIndexNames($schema, $table);
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('discussions', function (Blueprint $table) use ($schema) {
            $table->dropIndex(['last_posted_at']);
            $table->dropIndex(['last_posted_user_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['comment_count']);
            $table->dropIndex(['participant_count']);
            $table->dropIndex(['hidden_at']);

            Migration::fixIndexNames($schema, $table);
        });
    }
];
