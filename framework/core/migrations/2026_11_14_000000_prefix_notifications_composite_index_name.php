<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

// Gives installs from 2.0 the index name 2026_03_25_000000 now creates.
return [
    'up' => function (Builder $schema) {
        $connection = $schema->getConnection();

        // Only PostgreSQL and SQLite share index names between installs in one
        // database. MySQL and MariaDB keep the old name, which MariaDB before
        // 10.5.2 couldn't rename.
        if (! in_array($connection->getDriverName(), ['pgsql', 'sqlite'], true)) {
            return;
        }

        $columns = ['user_id', 'is_deleted', 'read_at', 'type'];

        // Laravel's name for it, from a blueprint that is never run.
        $name = (new Blueprint($connection, 'notifications'))->dropIndex($columns)->get('index');

        foreach ($schema->getIndexes('notifications') as $index) {
            if ($index['columns'] === $columns && strtolower($index['name']) !== $name) {
                $schema->table('notifications', function (Blueprint $table) use ($index, $name) {
                    $table->renameIndex($index['name'], $name);
                });
            }
        }
    },

    // 2026_03_25_000000 owns the index, and drops it under whichever name it has.
    'down' => function () {
    }
];
