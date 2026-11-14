<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

// The API keys of the connections set up on the admin page. Kept out of the
// settings table: the admin page is sent every setting, and a listener that
// removed them would stop doing so once the extension was disabled.
return Migration::createTable(
    'ai_connection_keys',
    function (Blueprint $table) {
        $table->string('connection', 50)->primary();
        $table->text('key');
        $table->dateTime('updated_at');
    }
);
