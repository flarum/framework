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
    'access_tokens',
    function (Blueprint $table) {
        $table->string('id', 100)->primary();
        $table->integer('user_id')->unsigned();
        $table->timestamp('created_at');
        $table->timestamp('expires_at');
    }
);
