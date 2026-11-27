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
    'notification_scope_claims',
    function (Blueprint $table) {
        $table->string('scope', 36);
        $table->unsignedInteger('user_id');
        // The notification driver: alert, email, ...
        $table->string('channel', 100);
        $table->string('claimed_by', 255);
        $table->dateTime('created_at')->index();

        $table->primary(['scope', 'user_id', 'channel']);
    }
);
