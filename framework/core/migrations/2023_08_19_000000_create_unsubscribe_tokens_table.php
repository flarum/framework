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
    'unsubscribe_tokens',
    function (Blueprint $table) {
        $table->id();
        $table->unsignedInteger('user_id');
        $table->string('email_type');
        $table->string('token', 100)->unique();
        $table->timestamp('unsubscribed_at')->nullable();
        $table->timestamps();

        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

        $table->index('user_id');
        $table->index('email_type');
        $table->index('token');
        $table->index(['user_id', 'email_type']);
    }
);
