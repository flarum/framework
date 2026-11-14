<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/*
 * The Extension Manager now keeps its runs under storage/ rather than in a tasks
 * table, and has no settings of its own. This drops the table, under either of
 * the names it has had, and the settings it kept under either extension ID.
 *
 * down() recreates the table as the earlier migrations left it, so their own
 * down()s can finish a rollback or purge.
 */
return [
    'up' => function (Builder $schema) {
        $schema->dropIfExists('extension_manager_tasks');
        $schema->dropIfExists('package_manager_tasks');

        $schema->getConnection()->table('settings')
            ->where('key', 'like', 'flarum-extension-manager.%')
            ->orWhere('key', 'like', 'flarum-package-manager.%')
            ->delete();
    },

    'down' => function (Builder $schema) {
        if ($schema->hasTable('extension_manager_tasks')) {
            return;
        }

        $schema->create('extension_manager_tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('status', 50)->nullable();
            $table->string('operation', 50);
            $table->string('command', 50)->nullable();
            $table->string('package', 100)->nullable();
            $table->mediumText('output');
            $table->string('guessed_cause', 255)->nullable();
            $table->timestamp('created_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedMediumInteger('peak_memory_used')->nullable();
        });
    },
];
