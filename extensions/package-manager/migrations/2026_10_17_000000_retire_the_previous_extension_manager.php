<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Builder;

/*
 * The previous Extension Manager queued its work as rows in a tasks table and
 * kept its options in settings. This one keeps every run under storage/ and has
 * no table, so both go, along with the record of the migrations that made them
 * (their files are gone, so nothing could reverse them).
 */
return [
    'up' => function (Builder $schema) {
        $schema->dropIfExists('extension_manager_tasks');

        $db = $schema->getConnection();

        $db->table('settings')->where('key', 'like', 'flarum-extension-manager.%')->delete();

        $db->table('migrations')->where('extension', 'flarum-extension-manager')->whereIn('migration', [
            '2022_02_22_000000_create_package_manager_tasks_table',
            '2023_12_09_000000_add_guessed_cause_column_to_package_manager_tasks_table',
            '2024_01_10_000000_rename_to_extension_manager',
        ])->delete();
    },

    'down' => function (Builder $schema) {
        // Nothing to restore: the previous Extension Manager's code is gone.
    },
];
