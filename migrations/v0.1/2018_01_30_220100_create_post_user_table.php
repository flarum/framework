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
    'post_user',
    function (Blueprint $table) {
        $table->integer('post_id')->unsigned();
        $table->integer('user_id')->unsigned();

        $table->primary(['post_id', 'user_id']);

        $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    }
);
