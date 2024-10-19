<?php

use Illuminate\Database\Schema\Blueprint;

use Flarum\Database\Migration;

return Migration::createTable(
    'dialog_message_mentions_post',
    function (Blueprint $table) {
        $table->unsignedInteger('dialog_message_id');
        $table->unsignedInteger('mentions_post_id');
        $table->dateTime('created_at')->nullable()->useCurrent();

        $table->primary(['dialog_message_id', 'mentions_post_id']);
        $table->foreign('dialog_message_id')->references('id')->on('dialog_messages')->cascadeOnDelete();
        $table->foreign('mentions_post_id')->references('id')->on('posts')->cascadeOnDelete();
    }
);

