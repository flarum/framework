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
        // Delete rows with non-existent users so that we will be able to create
        // foreign keys without any issues.
        $schema->getConnection()
            ->table('notifications')
            ->whereNotExists(function ($query) {
                $query->selectRaw(1)->from('users')->whereColumn('id', 'user_id');
            })
            ->delete();

        $schema->getConnection()
            ->table('notifications')
            ->whereNotExists(function ($query) {
                $query->selectRaw(1)->from('users')->whereColumn('id', 'from_user_id');
            })
            ->update(['from_user_id' => null]);

        $schema->table('notifications', function (Blueprint $table) use ($schema) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('from_user_id')->references('id')->on('users')->onDelete('set null');

            Migration::fixIndexNames($schema, $table);
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('notifications', function (Blueprint $table) use ($schema) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['from_user_id']);

            Migration::fixIndexNames($schema, $table);
        });
    }
];
