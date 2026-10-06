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
        // What the statistics count over time: conversations by when they
        // started, and replies by when they were sent. On 5,000,000 messages,
        // counting 56 days of replies went from 1.4s, reading the whole table,
        // to 93ms on MySQL.
        $schema->table('dialogs', function (Blueprint $table) {
            $table->index('created_at');
        });

        $schema->table('dialog_messages', function (Blueprint $table) {
            // `number` too, so telling a reply from a conversation's first
            // message is answered from the index without reading each row.
            $table->index(['created_at', 'number']);
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('dialog_messages', function (Blueprint $table) {
            $table->dropIndex(['created_at', 'number']);
        });

        $schema->table('dialogs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    },
];
