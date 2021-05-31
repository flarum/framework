<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Post\Post;
use Flarum\User\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->create('discussions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 200);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('participants_count')->default(0);
            $table->unsignedInteger('post_number_index')->default(0);

            $table->dateTime('created_at');
            $table->foreignIdFor(User::class, 'user_id')->nullable();
            $table->foreignIdFor(Post::class, 'first_post_id')->nullable();

            $table->dateTime('last_posted_at')->nullable();
            $table->foreignIdFor(User::class, 'last_posted_user_id')->nullable();
            $table->foreignIdFor(Post::class, 'last_post_id')->nullable();
            $table->unsignedInteger('last_post_number')->nullable();

            $table->dateTime('hidden_at')->nullable();
            $table->foreignIdFor(User::class, 'hidden_user_id')->nullable();

            $table->string('slug', 200);
            $table->boolean('is_private')->default(0);
        });

        $connection = $schema->getConnection();
        $prefix = $connection->getTablePrefix();
        $connection->statement('ALTER TABLE '.$prefix.'discussions ADD FULLTEXT title (title)');
    },

    'down'  => function (Builder $schema) {
        $schema->drop('discussions');
    },
];
