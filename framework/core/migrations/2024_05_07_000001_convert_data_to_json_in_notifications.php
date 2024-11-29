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

        if ($driver === 'pgsql') {
            $notifications = $connection->getSchemaGrammar()->wrapTable('notifications');
            $data = $connection->getSchemaGrammar()->wrap('data');
            $connection->statement("ALTER TABLE $notifications ALTER COLUMN $data TYPE JSON USING data::TEXT::JSON");
        } else {
            $schema->table('notifications', function (Blueprint $table) {
                $table->json('data_json')->nullable();
            });

            if ($connection instanceof MySqlConnection) {
                if ($connection->isMaria()) {
                    $connection->table('notifications')->update([
                        'data_json' => $connection->raw('IF(JSON_VALID(CONVERT(data USING utf8mb4)), CONVERT(data USING utf8mb4), NULL)'),
                    ]);
                } else {
                    $connection->table('notifications')->update([
                        'data_json' => $connection->raw('CAST(CONVERT(data USING utf8mb4) AS JSON)'),
                    ]);
                }
            }

            $schema->table('notifications', function (Blueprint $table) {
                $table->dropColumn('data');
            });

            $schema->table('notifications', function (Blueprint $table) {
                $table->renameColumn('data_json', 'data');
            });
        }
    },

    'down' => function (Builder $schema) {
        $connection = $schema->getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'pgsql') {
            $notifications = $connection->getSchemaGrammar()->wrapTable('notifications');
            $data = $connection->getSchemaGrammar()->wrap('data');
            $connection->statement("ALTER TABLE $notifications ALTER COLUMN $data TYPE BYTEA USING data::TEXT::BYTEA");
        } else {
            $schema->table('notifications', function (Blueprint $table) {
                $table->binary('data_binary')->nullable();
            });

            if ($connection instanceof MySqlConnection) {
                $connection->table('notifications')->update([
                    'data_binary' => $connection->raw('data'),
                ]);
            }

            $schema->table('notifications', function (Blueprint $table) {
                $table->dropColumn('data');
            });

            $schema->table('notifications', function (Blueprint $table) {
                $table->renameColumn('data_binary', 'data');
            });
        }
    }
];
