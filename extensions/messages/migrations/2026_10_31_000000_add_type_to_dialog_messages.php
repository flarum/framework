<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

// What a message is: something a member wrote, or a notice the conversation
// shows, such as a member leaving. Every existing message is the former.
return [
    'up' => function (Builder $schema) {
        $schema->table('dialog_messages', function (Blueprint $table) {
            $table->string('type', 100)->default('comment');
        });

        // The statistics count replies over time, which notices aren't: the
        // index they are answered from covers the type too, so that still
        // needs no row reads.
        $schema->table('dialog_messages', function (Blueprint $table) {
            $table->dropIndex(['created_at', 'number']);
            $table->index(['created_at', 'number', 'type']);
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('dialog_messages', function (Blueprint $table) {
            $table->dropIndex(['created_at', 'number', 'type']);
            $table->index(['created_at', 'number']);
        });

        $schema->table('dialog_messages', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    },
];
