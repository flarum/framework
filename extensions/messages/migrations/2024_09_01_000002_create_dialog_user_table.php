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
        $table->foreignId('dialog_id')->constrained()->cascadeOnDelete();
        $table->unsignedInteger('user_id');
        $table->dateTime('joined_at');
        $table->unsignedBigInteger('last_read_message_id')->default(0);
        $table->dateTime('last_read_at')->nullable();
        $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    }
);
