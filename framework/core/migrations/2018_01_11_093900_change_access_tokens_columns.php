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
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->renameColumn('id', 'token');
        });
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->renameColumn('lifetime', 'lifetime_seconds');
        });
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->renameColumn('last_activity', 'last_activity_at');
        });
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->dateTime('created_at');
            $table->integer('user_id')->unsigned()->change();
        });

        // Use a separate schema instance because this column gets renamed
        // in the previous one.
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->dateTime('last_activity_at')->change();
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->integer('last_activity_at')->change();
        });
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->renameColumn('token', 'id');
        });
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->renameColumn('lifetime_seconds', 'lifetime');
        });
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->renameColumn('last_activity_at', 'last_activity');
        });
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->dropColumn('created_at');
        });
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->integer('user_id')->change();
        });
    }
];
