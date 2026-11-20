<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

// For read receipts: the viewer's latest message at or before a read position.
return [
    'up' => function (Builder $schema) {
        $schema->table('dialog_messages', function (Blueprint $table) {
            $table->index(['dialog_id', 'user_id', 'type', 'id']);
        });
    },
    'down' => function (Builder $schema) {
        $schema->table('dialog_messages', function (Blueprint $table) {
            $table->dropIndex(['dialog_id', 'user_id', 'type', 'id']);
        });
    },
];
