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
        if ($schema->getConnection()->getDriverName() !== 'sqlite') {
            $schema->table('access_tokens', function (Blueprint $table) {
                // Replace primary key with unique index so we can create a new primary
                $table->dropPrimary('token');
                $table->unique('token');
            });

            // This needs to be done in a second statement because of the order Laravel runs operations in
            $schema->table('access_tokens', function (Blueprint $table) {
                // Introduce new increment-based ID
                $table->increments('id')->first();
            });
        } else {
            $schema->drop('access_tokens');
            $schema->create('access_tokens', function (Blueprint $table) {
                $table->increments('id');
                $table->string('token', 100)->unique();
                $table->integer('user_id')->unsigned();
                $table->dateTime('last_activity_at')->nullable();
                $table->dateTime('created_at');
                $table->string('type', 100)->nullable();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->index('type');
            });
        }
    },

    'down' => function (Builder $schema) {
        $schema->table('access_tokens', function (Blueprint $table) use ($schema) {
            $table->dropColumn('id');
            $table->dropIndex('token');

            if ($schema->getConnection()->getDriverName() !== 'sqlite') {
                $table->primary('token');
            }
        });
    }
];
