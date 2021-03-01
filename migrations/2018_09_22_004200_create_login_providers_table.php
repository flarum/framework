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
    'login_providers',
    function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('user_id');
        $table->string('provider', 100);
        $table->string('identifier', 100);
        $table->dateTime('created_at')->nullable();
        $table->dateTime('last_login_at')->nullable();

        $table->unique(['provider', 'identifier']);
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    }
);
