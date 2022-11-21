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
    'when' => function (Builder $schema) {
        return $schema->hasTable('tags');
    },
    'up' => function (Builder $schema) {
        $schema->create('post_mentions_tag', function (Blueprint $table) {
            $table->integer('post_id')->unsigned();
            $table->integer('mentions_tag_id')->unsigned();
            $table->dateTime('created_at')->useCurrent()->nullable();
            $table->primary(['post_id', 'mentions_tag_id']);

            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            $table->foreign('mentions_tag_id')->references('id')->on('tags')->onDelete('cascade');
        });
    },

    'down' => function (Builder $schema) {
        $schema->dropIfExists('post_mentions_tag');
    }
];
