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
        $schema->getConnection()
            ->table('discussion_tag')
            ->whereNotExists(function ($query) {
                $query->selectRaw(1)->from('discussions')->whereColumn('id', 'discussion_id');
            })
            ->orWhereNotExists(function ($query) {
                $query->selectRaw(1)->from('tags')->whereColumn('id', 'tag_id');
            })
            ->delete();

        $schema->table('discussion_tag', function (Blueprint $table) {
            $table->foreign('discussion_id')->references('id')->on('discussions')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('discussion_tag', function (Blueprint $table) {
            $table->dropForeign(['discussion_id']);
            $table->dropForeign(['tag_id']);
        });
    }
];
