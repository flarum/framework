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
        $schema->create('post_mentions_group', function (Blueprint $table) {
            $table->integer('post_id')->unsigned();
            $table->integer('mentions_group_id')->unsigned();
            $table->dateTime('created_at')->useCurrent()->nullable();
            $table->primary(['post_id', 'mentions_group_id']);

            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            $table->foreign('mentions_group_id')->references('id')->on('groups')->onDelete('cascade');
        });
    },

    'down' => function (Builder $schema) {
        $schema->drop('post_mentions_group');
    }
];
