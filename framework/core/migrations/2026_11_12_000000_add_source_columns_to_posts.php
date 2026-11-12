<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

// `created_via`, not `source`: the 1.x extension blomstra/conversations left its
// own `posts.source` behind on forums that used it.
//
// Nullable, no default and in one statement, so at most one table rebuild:
// instant on PostgreSQL and SQLite; on MySQL and MariaDB the FULLTEXT index on
// `content` rules out an instant change, so large forums should expect a rebuild.
return [
    'up' => function (Builder $schema) {
        $schema->table('posts', function (Blueprint $table) {
            $table->string('created_via', 50)->nullable();
            $table->string('created_via_name', 100)->nullable();
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('posts', function (Blueprint $table) {
            $table->dropColumn(['created_via', 'created_via_name']);
        });
    }
];
