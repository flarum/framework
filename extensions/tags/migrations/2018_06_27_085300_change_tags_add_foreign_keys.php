<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        // Set non-existent entity IDs to NULL so that we will be able to create
        // foreign keys without any issues.
        $connection = $schema->getConnection();

        $select = function ($id, $table, $column) use ($connection) {
            return new Expression(
                '('.$connection->table($table)->whereColumn('id', $column)->select($id)->toSql().')'
            );
        };

        $connection->table('tags')->update([
            'last_posted_user_id' => $select('last_posted_user_id', 'discussions', 'last_posted_discussion_id'),
            'last_posted_discussion_id' => $select('id', 'discussions', 'last_posted_discussion_id'),
        ]);

        $schema->table('tags', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('tags')->onDelete('set null');
            $table->foreign('last_posted_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('last_posted_discussion_id')->references('id')->on('discussions')->onDelete('set null');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('tags', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['last_posted_discussion_id']);
            $table->dropForeign(['last_posted_user_id']);
        });
    }
];
