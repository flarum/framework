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
        $connection->table('posts')
            ->whereNotExists(function ($query) {
                $query->selectRaw(1)->from('discussions')->whereColumn('id', 'discussion_id');
            })
            ->delete();

        $schema->table('posts', function (Blueprint $table) {
            $table->foreign('discussion_id')->references('id')->on('discussions')->onDelete('cascade');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('posts', function (Blueprint $table) {
            $table->dropForeign(['discussion_id']);
        });
    }
];
