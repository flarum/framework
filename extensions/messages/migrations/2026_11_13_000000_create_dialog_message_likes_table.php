<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

// Created whether or not flarum/likes is enabled, like the mention tables: it
// needs nothing of that extension's, only messages and users.
return Migration::createTable(
    'dialog_message_likes',
    function (Blueprint $table) {
        $table->unsignedInteger('dialog_message_id');
        $table->unsignedInteger('user_id');
        $table->dateTime('created_at')->nullable()->useCurrent();

        $table->primary(['dialog_message_id', 'user_id']);
        $table->foreign('dialog_message_id')->references('id')->on('dialog_messages')->cascadeOnDelete();
        $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    }
);
