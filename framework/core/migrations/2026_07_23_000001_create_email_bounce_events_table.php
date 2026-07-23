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
    'email_bounce_events',
    function (Blueprint $table) {
        $table->increments('id');
        $table->string('email');
        // Nullable: the address may not map to a user, and the user may later
        // be deleted/anonymized without losing the historical event.
        $table->unsignedInteger('user_id')->nullable();
        $table->string('type'); // 'bounce' | 'complaint'
        $table->string('reason')->nullable();
        $table->timestamp('created_at');

        $table->index('created_at');
        $table->index('user_id');

        $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
    }
);
