<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

// Mark-all as a watermark, like core's marked_all_as_read_at; a message ID
// because every unread check here compares message IDs.
return [
    'up' => function (Builder $schema) {
        $schema->table('users', function (Blueprint $table) {
            $table->unsignedInteger('marked_all_dialogs_read_id')->default(0);
        });
    },
    'down' => function (Builder $schema) {
        $schema->table('users', function (Blueprint $table) {
            $table->dropColumn('marked_all_dialogs_read_id');
        });
    },
];
