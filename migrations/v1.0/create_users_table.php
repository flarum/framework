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
    'users',
    function (Blueprint $table) {
        $table->increments('id');
        $table->string('username', 100)->unique();
        $table->string('email', 150)->unique();
        $table->boolean('is_email_confirmed')->default(0);
        $table->string('password', 100);
        $table->string('avatar_url', 100)->nullable();
        $table->text('preferences')->nullable();
        $table->dateTime('join_time')->nullable();
        $table->dateTime('last_seen_time')->nullable();
        $table->dateTime('read_time')->nullable();
        $table->dateTime('notification_read_time')->nullable();
        $table->integer('discussions_count')->unsigned()->default(0);
        $table->integer('comments_count')->unsigned()->default(0);

        $table->index('joined_at');
        $table->index('last_seen_at');
        $table->index('discussion_count');
        $table->index('comment_count');
    }
);
