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
        $definition = function (Blueprint $table) {
            $table->increments('id');
            $table->string('allowed_ips')->nullable();
            $table->string('scopes')->nullable();
            $table->integer('user_id')->unsigned()->nullable();
            $table->dateTime('created_at');
            $table->dateTime('last_activity_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        };

        if ($schema->getConnection()->getDriverName() !== 'sqlite') {
            $schema->table('api_keys', function (Blueprint $table) {
                $table->dropPrimary(['id']);
                $table->renameColumn('id', 'key');
                $table->unique('key');
            });

            $schema->table('api_keys', $definition);
        } else {
            $schema->drop('api_keys');
            $schema->create('api_keys', function (Blueprint $table) use ($definition) {
                $table->string('key', 100)->unique();
                $definition($table);
            });
        }
    },

    'down' => function (Builder $schema) {
        $schema->table('api_keys', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('id', 'allowed_ips', 'user_id', 'scopes', 'created_at');
        });

        $schema->table('api_keys', function (Blueprint $table) use ($schema) {
            $table->dropUnique(['key']);
            $table->renameColumn('key', 'id');

            if ($schema->getConnection()->getDriverName() !== 'sqlite') {
                $table->primary('id');
            }
        });
    }
];
