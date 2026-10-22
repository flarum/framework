<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('extension_manager_tasks', function (Blueprint $table) {
            $table->text('command')->nullable()->change();
        });
    },
    'down' => function (Builder $schema) {
        $schema->table('extension_manager_tasks', function (Blueprint $table) {
            $table->string('command', 50)->nullable()->change();
        });
    }
];
