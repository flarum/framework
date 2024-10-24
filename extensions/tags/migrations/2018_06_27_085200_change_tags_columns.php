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
        $schema->table('tags', function (Blueprint $table) {
            $table->renameColumn('discussions_count', 'discussion_count');
        });
        $schema->table('tags', function (Blueprint $table) {
            $table->renameColumn('last_time', 'last_posted_at');
        });
        $schema->table('tags', function (Blueprint $table) {
            $table->renameColumn('last_discussion_id', 'last_posted_discussion_id');
        });
        $schema->table('tags', function (Blueprint $table) {
            $table->integer('parent_id')->unsigned()->nullable()->change();

            $table->integer('last_posted_user_id')->unsigned()->nullable();
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('tags', function (Blueprint $table) {
            $table->dropColumn('last_posted_user_id');
        });
        $schema->table('tags', function (Blueprint $table) {
            $table->integer('parent_id')->nullable()->change();
        });
        $schema->table('tags', function (Blueprint $table) {
            $table->renameColumn('discussion_count', 'discussions_count');
        });
        $schema->table('tags', function (Blueprint $table) {
            $table->renameColumn('last_posted_at', 'last_time');
        });
        $schema->table('tags', function (Blueprint $table) {
            $table->renameColumn('last_posted_discussion_id', 'last_discussion_id');
        });
    }
];
