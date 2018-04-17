<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('discussions', function (Blueprint $table) {
            $table->renameColumn('comments_count', 'comment_count');
            $table->renameColumn('participants_count', 'participant_count');
            $table->renameColumn('number_index', 'post_number_index');
            $table->renameColumn('start_time', 'created_at');
            $table->renameColumn('start_user_id', 'user_id');
            $table->renameColumn('start_post_id', 'first_post_id');
            $table->renameColumn('last_time', 'last_posted_at');
            $table->renameColumn('last_user_id', 'last_posted_user_id');
            $table->renameColumn('hide_time', 'hidden_at');
            $table->renameColumn('hide_user_id', 'hidden_user_id');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('discussions', function (Blueprint $table) {
            $table->renameColumn('comment_count', 'comments_count');
            $table->renameColumn('participant_count', 'participants_count');
            $table->renameColumn('post_number_index', 'number_index');
            $table->renameColumn('created_at', 'start_time');
            $table->renameColumn('user_id', 'start_user_id');
            $table->renameColumn('first_post_id', 'start_post_id');
            $table->renameColumn('last_posted_at', 'last_time');
            $table->renameColumn('last_posted_user_id', 'last_user_id');
            $table->renameColumn('hidden_at', 'hide_time');
            $table->renameColumn('hidden_user_id', 'hide_user_id');
        });
    }
];
