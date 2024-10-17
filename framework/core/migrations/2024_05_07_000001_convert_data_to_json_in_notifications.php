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
            $notifications = $schema->getConnection()->getSchemaGrammar()->wrapTable('notifications');
            $data = $schema->getConnection()->getSchemaGrammar()->wrap('data');
            $schema->getConnection()->statement("ALTER TABLE $notifications ALTER COLUMN $data TYPE JSON USING data::TEXT::JSON");
        } else {
            $schema->table('notifications', function (Blueprint $table) {
                $table->json('data_json')->nullable();
            });

            if ($schema->getConnection()->getDriverName() === 'mysql') {
                $schema->getConnection()->table('notifications')->update([
                    'data_json' => $schema->getConnection()->raw('CAST(CONVERT(data USING utf8mb4) AS JSON)'),
                ]);
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
        if ($schema->getConnection()->getDriverName() === 'pgsql') {
            $notifications = $schema->getConnection()->getSchemaGrammar()->wrapTable('notifications');
            $data = $schema->getConnection()->getSchemaGrammar()->wrap('data');
            $schema->getConnection()->statement("ALTER TABLE $notifications ALTER COLUMN $data TYPE BYTEA USING data::TEXT::BYTEA");
        } else {
            $schema->table('notifications', function (Blueprint $table) {
                $table->binary('data_binary')->nullable();
            });

            if ($schema->getConnection()->getDriverName() === 'mysql') {
                $schema->getConnection()->table('notifications')->update([
                    'data_binary' => $schema->getConnection()->raw('data'),
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
