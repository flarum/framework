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
        if ($schema->getConnection()->getDriverName() !== 'sqlite') {
            $schema->table('discussions', function (Blueprint $table) {
                $table->fullText('title');
            });
        }
    },

    'down' => function (Builder $schema) {
        if ($schema->getConnection()->getDriverName() !== 'sqlite') {
            $schema->table('discussions', function (Blueprint $table) {
                $table->dropFullText('title');
            });
        }
    }
];
