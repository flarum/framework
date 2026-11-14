<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/*
 * The notifications composite index was first created without the table prefix,
 * and index names are schema-wide on PostgreSQL and SQLite, so a second install
 * sharing the database could not be migrated. Give it the prefixed name that
 * fresh installs now get.
 */

$unprefixed = 'notifications_user_unread_type_index';

$rename = function (Builder $schema, string $from, string $to) {
    if ($from === $to) {
        return;
    }

    $names = array_column($schema->getIndexes('notifications'), 'name');

    // Only an index on this install's own table: on PostgreSQL and SQLite the
    // unprefixed name may belong to another install's notifications table.
    if (in_array($from, $names, true) && ! in_array($to, $names, true)) {
        $schema->table('notifications', function (Blueprint $table) use ($from, $to) {
            $table->renameIndex($from, $to);
        });
    }
};

return [
    'up' => function (Builder $schema) use ($unprefixed, $rename) {
        $rename($schema, $unprefixed, $schema->getConnection()->getTablePrefix().$unprefixed);
    },

    'down' => function (Builder $schema) use ($unprefixed, $rename) {
        $rename($schema, $schema->getConnection()->getTablePrefix().$unprefixed, $unprefixed);
    }
];
