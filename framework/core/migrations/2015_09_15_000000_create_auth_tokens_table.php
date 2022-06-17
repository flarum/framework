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
    'auth_tokens',
    static function (Blueprint $table) {
        $table->string('id', 100)->primary();
        $table->string('payload', 150);
        $table->timestamp('created_at');
    }
);
