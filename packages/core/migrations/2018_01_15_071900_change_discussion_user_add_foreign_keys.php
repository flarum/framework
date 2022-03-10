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
        // Delete rows with non-existent entities so that we will be able to create
        // foreign keys without any issues.
        $connection = $schema->getConnection();
        $connection->table('discussion_user')
            ->whereNotExists(function ($query) {
                $query->selectRaw(1)->from('users')->whereColumn('id', 'user_id');
            })
            ->orWhereNotExists(function ($query) {
                $query->selectRaw(1)->from('discussions')->whereColumn('id', 'discussion_id');
            })
            ->delete();

        $schema->table('discussion_user', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('discussion_id')->references('id')->on('discussions')->onDelete('cascade');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('discussion_user', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['discussion_id']);
        });
    }
];
