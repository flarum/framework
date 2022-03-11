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
        $schema->table('api_keys', function (Blueprint $table) {
            $table->dropPrimary(['id']);
            $table->renameColumn('id', 'key');
            $table->unique('key');
        });

        $schema->table('api_keys', function (Blueprint $table) {
            $table->increments('id');
            $table->string('allowed_ips')->nullable();
            $table->string('scopes')->nullable();
            $table->integer('user_id')->unsigned()->nullable();
            $table->dateTime('created_at');
            $table->dateTime('last_activity_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('api_keys', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('id', 'allowed_ips', 'user_id', 'scopes', 'created_at');
        });

        $schema->table('api_keys', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->renameColumn('key', 'id');
            $table->primary('id');
        });
    }
];
