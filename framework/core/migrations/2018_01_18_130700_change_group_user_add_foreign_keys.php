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
    'up' => static function (Builder $schema) {
        // Delete rows with non-existent entities so that we will be able to create
        // foreign keys without any issues.
        $schema->getConnection()
            ->table('group_user')
            ->whereNotExists(static function ($query) {
                $query->selectRaw(1)->from('users')->whereColumn('id', 'user_id');
            })
            ->orWhereNotExists(static function ($query) {
                $query->selectRaw(1)->from('groups')->whereColumn('id', 'group_id');
            })
            ->delete();

        $schema->table('group_user', static function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
        });
    },

    'down' => static function (Builder $schema) {
        $schema->table('group_user', static function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['group_id']);
        });
    }
];
