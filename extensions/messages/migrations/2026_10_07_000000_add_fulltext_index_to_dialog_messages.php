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
        // What searching messages by content matches against, as posts have.
        // SQLite has no fulltext index; it searches by substring instead.
        if ($schema->getConnection()->getDriverName() !== 'sqlite') {
            $schema->table('dialog_messages', function (Blueprint $table) {
                $table->fullText('content');
            });
        }
    },

    'down' => function (Builder $schema) {
        if ($schema->getConnection()->getDriverName() !== 'sqlite') {
            $schema->table('dialog_messages', function (Blueprint $table) {
                $table->dropFullText(['content']);
            });
        }
    },
];
