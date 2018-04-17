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
        $schema->table('users', function (Blueprint $table) {
            $table->renameColumn('is_activated', 'is_email_confirmed');
            $table->renameColumn('join_time', 'joined_at');
            $table->renameColumn('last_seen_time', 'last_seen_at');
            $table->renameColumn('discussions_count', 'discussion_count');
            $table->renameColumn('comments_count', 'comment_count');
            $table->renameColumn('read_time', 'marked_all_as_read_at');
            $table->renameColumn('notifications_read_time', 'read_notifications_at');
            $table->renameColumn('avatar_path', 'avatar_url');
            $table->dropColumn('bio', 'preferences');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('users', function (Blueprint $table) {
            $table->renameColumn('is_email_confirmed', 'is_activated');
            $table->renameColumn('joined_at', 'join_time');
            $table->renameColumn('last_seen_at', 'last_seen_time');
            $table->renameColumn('discussion_count', 'discussions_count');
            $table->renameColumn('comment_count', 'comments_count');
            $table->renameColumn('marked_all_as_read_at', 'read_time');
            $table->renameColumn('read_notifications_at', 'notifications_read_time');
            $table->renameColumn('avatar_url', 'avatar_path');
            $table->text('bio')->nullable();
            $table->binary('preferences')->nullable();
        });
    }
];
