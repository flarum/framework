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
        if (! $schema->hasTable('gdpr_exports')) {
            $schema->create('gdpr_exports', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->nullable();
                $table->integer('actor_id')->unsigned()->nullable();
                $table->string('file')->nullable();
                $table->dateTime('created_at');
                $table->dateTime('destroys_at');

                $table->foreign('actor_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    },
    'down' => function (Builder $schema) {
        $schema->dropIfExists('gdpr_exports');
    },
];
