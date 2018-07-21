<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable(
    'user_user',
    function (Blueprint $table) {
        $table->integer('user_id')->unsigned();
        $table->integer('other_user_id')->unsigned();

        $table->foreign('user_id')->references('id')->on('posts')->onDelete('cascade');
        $table->foreign('other_user_id')->references('id')->on('users')->onDelete('cascade');
    }
);
