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
    'dialogs',
    function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->unsignedBigInteger('first_message_id')->nullable();
        $table->unsignedBigInteger('last_message_id')->nullable();
        $table->dateTime('last_message_at')->nullable();
        $table->unsignedInteger('last_message_user_id')->nullable();
        $table->foreign('last_message_user_id')->references('id')->on('users')->nullOnDelete();
        $table->string('type');
        $table->timestamps();
    }
);
