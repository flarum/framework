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
        if ($schema->getConnection()->getDriverName() === 'pgsql') {
            $users = $schema->getConnection()->getSchemaGrammar()->wrapTable('users');
            $preferences = $schema->getConnection()->getSchemaGrammar()->wrap('preferences');
            $schema->getConnection()->statement("ALTER TABLE $users ALTER COLUMN $preferences TYPE JSON USING preferences::TEXT::JSON");
        } else {
            $schema->table('users', function (Blueprint $table) {
                $table->json('preferences')->nullable()->change();
            });
        }
    },

    'down' => function (Builder $schema) {
        if ($schema->getConnection()->getDriverName() === 'pgsql') {
            $users = $schema->getConnection()->getSchemaGrammar()->wrapTable('users');
            $preferences = $schema->getConnection()->getSchemaGrammar()->wrap('preferences');
            $schema->getConnection()->statement("ALTER TABLE $users ALTER COLUMN $preferences TYPE BYTEA USING preferences::TEXT::BYTEA");
        } else {
            $schema->table('users', function (Blueprint $table) {
                $table->binary('preferences')->nullable()->change();
            });
        }
    }
];
