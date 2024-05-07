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
        if ($schema->getConnection()->getDriverName() !== 'pgsql') {
            $schema->table('notifications', function (Blueprint $table) {
                $table->json('data')->nullable()->change();
            });
        } else {
            $notifications = $schema->getConnection()->getSchemaGrammar()->wrapTable('notifications');
            $data = $schema->getConnection()->getSchemaGrammar()->wrap('data');
            $schema->getConnection()->statement("ALTER TABLE $notifications ALTER COLUMN $data TYPE JSON USING data::TEXT::JSON");
        }
    },

    'down' => function (Builder $schema) {
        if ($schema->getConnection()->getDriverName() !== 'pgsql') {
            $schema->table('notifications', function (Blueprint $table) {
                $table->binary('data')->nullable()->change();
            });
        } else {
            $notifications = $schema->getConnection()->getSchemaGrammar()->wrapTable('notifications');
            $data = $schema->getConnection()->getSchemaGrammar()->wrap('data');
            $schema->getConnection()->statement("ALTER TABLE $notifications ALTER COLUMN $data TYPE BYTEA USING data::TEXT::BYTEA");
        }
    }
];
