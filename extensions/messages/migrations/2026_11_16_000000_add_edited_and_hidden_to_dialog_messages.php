<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

// Who last edited a message and when, and who deleted it (soft) and when. The
// users stay optional: removing an account keeps what it did to others' messages.
return [
    'up' => function (Builder $schema) {
        $schema->table('dialog_messages', function (Blueprint $table) {
            $table->dateTime('edited_at')->nullable();
            $table->unsignedInteger('edited_user_id')->nullable();
            $table->dateTime('hidden_at')->nullable();
            $table->unsignedInteger('hidden_user_id')->nullable();

            // PostgreSQL doesn't index a foreign key by itself, and deleting
            // a user looks for their rows here.
            $table->index('edited_user_id');
            $table->index('hidden_user_id');

            $table->foreign('edited_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('hidden_user_id')->references('id')->on('users')->nullOnDelete();
        });
    },
    'down' => function (Builder $schema) {
        $schema->table('dialog_messages', function (Blueprint $table) {
            $table->dropForeign(['edited_user_id']);
            $table->dropForeign(['hidden_user_id']);
            $table->dropIndex(['edited_user_id']);
            $table->dropIndex(['hidden_user_id']);
            $table->dropColumn(['edited_at', 'edited_user_id', 'hidden_at', 'hidden_user_id']);
        });
    },
];
