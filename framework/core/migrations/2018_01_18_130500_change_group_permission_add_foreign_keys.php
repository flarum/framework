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
        // Delete rows with non-existent groups so that we will be able to create
        // foreign keys without any issues.
        $schema->getConnection()
            ->table('group_permission')
            ->whereNotExists(static function ($query) {
                $query->selectRaw(1)->from('groups')->whereColumn('id', 'group_id');
            })
            ->delete();

        $schema->table('group_permission', static function (Blueprint $table) {
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
        });
    },

    'down' => static function (Builder $schema) {
        $schema->table('group_permission', static function (Blueprint $table) {
            $table->dropForeign(['group_id']);
        });
    }
];
