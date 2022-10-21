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
        $schema->table('post_mentions_group', function (Blueprint $table) {
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            $table->foreign('mentions_group_id')->references('id')->on('groups')->onDelete('cascade');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('post_mentions_group', function (Blueprint $table) {
            $table->dropForeign(['post_id']);
            $table->dropForeign(['mentions_group_id']);
        });
    }
];