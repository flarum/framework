<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

// We need a full custom migration here, because we need to add the fulltext
// index for the content with a raw SQL statement after creating the table.
return [
    'up' => function (Builder $schema) {
        $schema->create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('discussion_id')->unsigned();
            $table->integer('number')->unsigned()->nullable();

            $table->dateTime('time');
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('type', 100)->nullable();
            $table->text('content')->nullable();

            $table->dateTime('edit_time')->nullable();
            $table->integer('edit_user_id')->unsigned()->nullable();
            $table->dateTime('hide_time')->nullable();
            $table->integer('hide_user_id')->unsigned()->nullable();

            $table->unique(['discussion_id', 'number']);
        });

        if ($schema->getConnection()->getDriverName() !== 'sqlite') {
            $schema->table('posts', function (Blueprint $table) {
                $table->fullText('content');
            });
        }
    },

    'down' => function (Builder $schema) {
        $schema->drop('posts');
    }
];
