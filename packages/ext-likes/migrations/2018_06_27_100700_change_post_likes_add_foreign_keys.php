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
        // Delete rows with non-existent entities so that we will be able to create
        // foreign keys without any issues.
        $schema->getConnection()
            ->table('post_likes')
            ->whereNotExists(function ($query) {
                $query->selectRaw(1)->from('posts')->whereColumn('id', 'post_id');
            })
            ->orWhereNotExists(function ($query) {
                $query->selectRaw(1)->from('users')->whereColumn('id', 'user_id');
            })
            ->delete();

        $schema->table('post_likes', function (Blueprint $table) use ($schema) {
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            Migration::fixIndexNames($schema, $table);
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('post_likes', function (Blueprint $table) use ($schema) {
            $table->dropForeign(['post_id']);
            $table->dropForeign(['user_id']);

            Migration::fixIndexNames($schema, $table);
        });
    }
];
