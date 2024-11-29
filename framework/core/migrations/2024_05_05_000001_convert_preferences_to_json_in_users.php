<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\MySqlConnection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $connection = $schema->getConnection();
        $driver = $connection->getDriverName();

        $preferences = $connection->getSchemaGrammar()->wrap('preferences');

        if ($driver === 'pgsql') {
            $users = $connection->getSchemaGrammar()->wrapTable('users');
            $connection->statement("ALTER TABLE $users ALTER COLUMN $preferences TYPE JSON USING $preferences::TEXT::JSON");
        } else {
            $schema->table('users', function (Blueprint $table) {
                $table->json('preferences_json')->nullable();
            });

            if ($connection instanceof MySqlConnection) {
                if ($connection->isMaria()) {
                    $connection->table('users')->update([
                        'preferences_json' => $connection->raw("IF(JSON_VALID(CONVERT($preferences USING utf8mb4)), CONVERT($preferences USING utf8mb4), NULL)"),
                    ]);
                } else {
                    $connection->table('users')->update([
                        'preferences_json' => $connection->raw("CAST(CONVERT($preferences USING utf8mb4) AS JSON)"),
                    ]);
                }
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
        $connection = $schema->getConnection();
        $driver = $connection->getDriverName();

        $preferences = $connection->getSchemaGrammar()->wrap('preferences');

        if ($driver === 'pgsql') {
            $users = $connection->getSchemaGrammar()->wrapTable('users');
            $connection->statement("ALTER TABLE $users ALTER COLUMN $preferences TYPE BYTEA USING preferences::TEXT::BYTEA");
        } else {
            $schema->table('users', function (Blueprint $table) {
                $table->binary('preferences_binary')->nullable();
            });

            if ($connection instanceof MySqlConnection) {
                $connection->table('users')->update([
                    'preferences_binary' => $connection->raw($preferences),
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
