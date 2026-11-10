<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTableIfNotExists(
    'package_manager_tasks',
    function (Blueprint $table) {
        $table->increments('id');
        $table->string('status', 50)->nullable();
        $table->string('operation', 50);
        $table->string('command', 50)->nullable();
        $table->string('package', 100)->nullable();
        $table->mediumText('output');
        $table->timestamp('created_at');
        $table->timestamp('started_at')->nullable();
        $table->timestamp('finished_at')->nullable();
        // Saved in KB
        $table->unsignedMediumInteger('peak_memory_used')->nullable();
    }
);
