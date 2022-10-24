<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable(
    'post_mentions_group',
    function (Blueprint $table) {
        $table->integer('post_id')->unsigned();
        $table->integer('mentions_group_id')->unsigned();
        $table->timestamp('created_at')->useCurrent()->nullable();
        $table->primary(['post_id', 'mentions_group_id']);

        $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
        $table->foreign('mentions_group_id')->references('id')->on('groups')->onDelete('cascade');
    }
);
