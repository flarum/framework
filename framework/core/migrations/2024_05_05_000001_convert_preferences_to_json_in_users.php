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
        $preferences = $schema->getConnection()->getSchemaGrammar()->wrap('preferences');

        if ($schema->getConnection()->getDriverName() === 'pgsql') {
            $users = $schema->getConnection()->getSchemaGrammar()->wrapTable('users');
            $schema->getConnection()->statement("ALTER TABLE $users ALTER COLUMN $preferences TYPE JSON USING $preferences::TEXT::JSON");
        } else {
            $schema->table('users', function (Blueprint $table) {
                $table->json('preferences_json')->nullable();
            });

            if ($schema->getConnection()->getDriverName() === 'mysql') {
                $schema->getConnection()->table('users')->update([
                    'preferences_json' => $schema->getConnection()->raw("CAST(CONVERT($preferences USING utf8mb4) AS JSON)"),
                ]);
            }

            $schema->table('users', function (Blueprint $table) {
                $table->dropColumn('preferences');
            });

            $schema->table('users', function (Blueprint $table) {
                $table->renameColumn('preferences_json', 'preferences');
            });
        }
    },

    'down' => function (Builder $schema) {
        $preferences = $schema->getConnection()->getSchemaGrammar()->wrap('preferences');

        if ($schema->getConnection()->getDriverName() === 'pgsql') {
            $users = $schema->getConnection()->getSchemaGrammar()->wrapTable('users');
            $schema->getConnection()->statement("ALTER TABLE $users ALTER COLUMN $preferences TYPE BYTEA USING preferences::TEXT::BYTEA");
        } else {
            $schema->table('users', function (Blueprint $table) {
                $table->binary('preferences_binary')->nullable();
            });

            if ($schema->getConnection()->getDriverName() === 'mysql') {
                $schema->getConnection()->table('users')->update([
                    'preferences_binary' => $schema->getConnection()->raw($preferences),
                ]);
            }

            $schema->table('users', function (Blueprint $table) {
                $table->dropColumn('preferences');
            });

            $schema->table('users', function (Blueprint $table) {
                $table->renameColumn('preferences_binary', 'preferences');
            });
        }
    }
];
