<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

// For the `source:` filter, which lists the newest posts first. Building it
// scans `posts` once; on PostgreSQL it holds off writes to the table until done.
return [
    'up' => function (Builder $schema) {
        $schema->table('posts', function (Blueprint $table) {
            $table->index(['source', 'created_at']);
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('posts', function (Blueprint $table) {
            $table->dropIndex(['source', 'created_at']);
        });
    }
];
