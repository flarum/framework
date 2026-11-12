<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

// Nullable, no default and in one statement, so at most one table rebuild:
// instant on PostgreSQL and SQLite; on MySQL and MariaDB the FULLTEXT index on
// `content` rules out an instant change, so large forums should expect a rebuild.
return [
    'up' => function (Builder $schema) {
        $schema->table('posts', function (Blueprint $table) {
            $table->string('source', 50)->nullable();
            $table->string('source_name', 100)->nullable();
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('posts', function (Blueprint $table) {
            $table->dropColumn(['source', 'source_name']);
        });
    }
];
