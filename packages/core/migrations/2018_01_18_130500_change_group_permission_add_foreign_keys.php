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
        // Delete rows with non-existent groups so that we will be able to create
        // foreign keys without any issues.
        $schema->getConnection()
            ->table('group_permission')
            ->whereNotExists(function ($query) {
                $query->selectRaw(1)->from('groups')->whereColumn('id', 'group_id');
            })
            ->delete();

        $schema->table('group_permission', function (Blueprint $table) use ($schema) {
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');

            Migration::fixIndexNames($schema, $table);
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('group_permission', function (Blueprint $table) use ($schema) {
            $table->dropForeign(['group_id']);

            Migration::fixIndexNames($schema, $table);
        });
    }
];
