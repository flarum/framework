<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        // Set non-existent entity IDs to NULL so that we will be able to create
        // foreign keys without any issues.
        $connection = $schema->getConnection();

        $selectId = function ($table, $column) use ($connection) {
            return new Expression(
                '('.$connection->table($table)->whereColumn('id', $column)->select('id')->toSql().')'
            );
        };

        $connection->table('discussions')->update([
            'user_id' => $selectId('users', 'user_id'),
            'last_posted_user_id' => $selectId('users', 'last_posted_user_id'),
            'hidden_user_id' => $selectId('users', 'hidden_user_id'),
            'first_post_id' => $selectId('posts', 'first_post_id'),
            'last_post_id' => $selectId('posts', 'last_post_id'),
        ]);

        $schema->table('discussions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('last_posted_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('hidden_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('first_post_id')->references('id')->on('posts')->onDelete('set null');
            $table->foreign('last_post_id')->references('id')->on('posts')->onDelete('set null');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('discussions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['last_posted_user_id']);
            $table->dropForeign(['hidden_user_id']);
            $table->dropForeign(['first_post_id']);
            $table->dropForeign(['last_post_id']);
        });
    }
];
