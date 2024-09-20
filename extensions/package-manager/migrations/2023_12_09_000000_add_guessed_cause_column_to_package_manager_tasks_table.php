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
        $schema->table('package_manager_tasks', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('package_manager_tasks', 'guessed_cause')) {
                $table->string('guessed_cause', 255)->nullable()->after('output');
            }
        });
    },
    'down' => function (Builder $schema) {
        $schema->table('package_manager_tasks', function (Blueprint $table) use ($schema) {
            if ($schema->hasColumn('package_manager_tasks', 'guessed_cause')) {
                $table->dropColumn('guessed_cause');
            }
        });
    }
];
