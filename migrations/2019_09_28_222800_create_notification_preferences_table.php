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
        $schema->create('notification_preferences', function (Blueprint $table) {
            $table->integer('user_id')->unsigned();
            $table->string('type');
            $table->string('channel');
            $table->boolean('enabled')->default(false);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    },

    'down' => function (Builder $schema) {
        $schema->drop('notification_preferences');
    }
];
