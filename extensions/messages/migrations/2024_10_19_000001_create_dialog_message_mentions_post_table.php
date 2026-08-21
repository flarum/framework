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
    'message_mentions_post',
    function (Blueprint $table) {
        $table->unsignedInteger('message_id');
        $table->unsignedInteger('post_id');
        $table->dateTime('created_at')->nullable()->useCurrent();

        $table->primary(['message_id', 'post_id']);
        $table->foreign('message_id')->references('id')->on('dialog_messages')->cascadeOnDelete();
        $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
    }
);
