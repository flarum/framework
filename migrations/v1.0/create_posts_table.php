<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Discussion\Discussion;
use Flarum\User\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

// We need a full custom migration here, because we need to add the fulltext
// index for the content with a raw SQL statement after creating the table.
return [
    'up' => function (Builder $schema) {
        $schema->create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignIdFor(Discussion::class, 'discussion_id');
            $table->unsignedInteger('number')->nullable();

            $table->dateTime('created_at');
            $table->foreignIdFor(User::class, 'user_id')->nullable();
            $table->string('type', 100)->nullable();
            $table->mediumText('content')->nullable();

            $table->dateTime('edited_at')->nullable();
            $table->foreignIdFor(User::class, 'edited_user_id')->nullable();
            $table->dateTime('hidden_at')->nullable();
            $table->foreignIdFor(User::class, 'hidden_user_id')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->boolean('is_private');

            $table->unique(['discussion_id', 'number']);
        });

        $connection = $schema->getConnection();
        $prefix = $connection->getTablePrefix();
        $connection->statement('ALTER TABLE '.$prefix.'posts ADD FULLTEXT content (content)');
    },

    'down' => function (Builder $schema) {
        $schema->drop('posts');
    }
];
