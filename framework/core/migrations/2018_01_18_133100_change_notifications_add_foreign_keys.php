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
        // Delete rows with non-existent users so that we will be able to create
        // foreign keys without any issues.
        $schema->getConnection()
            ->table('notifications')
            ->whereNotExists(static function ($query) {
                $query->selectRaw(1)->from('users')->whereColumn('id', 'user_id');
            })
            ->delete();

        $schema->getConnection()
            ->table('notifications')
            ->whereNotExists(static function ($query) {
                $query->selectRaw(1)->from('users')->whereColumn('id', 'from_user_id');
            })
            ->update(['from_user_id' => null]);

        $schema->table('notifications', static function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('from_user_id')->references('id')->on('users')->onDelete('set null');
        });
    },

    'down' => static function (Builder $schema) {
        $schema->table('notifications', static function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['from_user_id']);
        });
    }
];
