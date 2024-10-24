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
    'dialog_messages',
    function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('dialog_id');
        $table->foreign('dialog_id')->references('id')->on('dialogs')->cascadeOnDelete();
        $table->unsignedInteger('user_id')->nullable();
        $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        $table->text('content');
        $table->timestamps();
    }
);
