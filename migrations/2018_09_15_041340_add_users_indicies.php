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
        $schema->table('users', function (Blueprint $table) use ($schema) {
            $table->index('joined_at');
            $table->index('last_seen_at');
            $table->index('discussion_count');
            $table->index('comment_count');

            Migration::fixIndexNames($schema, $table);
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('users', function (Blueprint $table) use ($schema) {
            $table->dropIndex(['joined_at']);
            $table->dropIndex(['last_seen_at']);
            $table->dropIndex(['discussion_count']);
            $table->dropIndex(['comment_count']);

            Migration::fixIndexNames($schema, $table);
        });
    }
];
