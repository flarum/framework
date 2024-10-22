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
    'dialog_user',
    function (Blueprint $table) {
        $table->id();
        $table->unsignedInteger('dialog_id');
        $table->foreign('dialog_id')->references('id')->on('dialogs')->cascadeOnDelete();
        $table->unsignedInteger('user_id');
        $table->dateTime('joined_at');
        $table->unsignedInteger('last_read_message_id')->default(0);
        $table->dateTime('last_read_at')->nullable();
        $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    }
);
