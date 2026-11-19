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
        $schema->table('dialog_user', function (Blueprint $table) {
            // Existing rows are individual recipients, including upgrades from 2.0.
            $table->boolean('is_recipient')->default(true);
            $table->dateTime('left_at')->nullable();
            $table->unsignedInteger('last_notified_message_id')->nullable();
        });
        $schema->table('dialogs', fn (Blueprint $table) => $table->unsignedInteger('recipient_count')->nullable());
        $schema->table('dialog_messages', fn (Blueprint $table) => $table->index(['type', 'dialog_id', 'id']));
        $schema->create('dialog_group', function (Blueprint $table) {
            $table->unsignedInteger('dialog_id');
            $table->unsignedInteger('group_id');
            $table->primary(['dialog_id', 'group_id']);
            $table->index(['group_id', 'dialog_id']);
            $table->foreign('dialog_id')->references('id')->on('dialogs')->cascadeOnDelete();
            $table->foreign('group_id')->references('id')->on('groups')->cascadeOnDelete();
        });
    },
    'down' => function (Builder $schema) {
        // Older Messages treats every state row as individual membership.
        $schema->getConnection()->table('dialog_user')->where(function ($query) {
            $query->where('is_recipient', false)->orWhereNotNull('left_at');
        })->delete();
        $schema->drop('dialog_group');
        $schema->table('dialog_messages', fn (Blueprint $table) => $table->dropIndex(['type', 'dialog_id', 'id']));
        $schema->table('dialogs', fn (Blueprint $table) => $table->dropColumn('recipient_count'));
        $schema->table('dialog_user', function (Blueprint $table) {
            $table->dropColumn(['is_recipient', 'left_at', 'last_notified_message_id']);
        });
    },
];
