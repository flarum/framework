<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->rename('package_manager_tasks', 'extension_manager_tasks');
        $schema->getConnection()->table('migrations')->where('extension', 'flarum-package-manager')->delete();
    },
    'down' => function (Builder $schema) {
        // The migration rows are left alone: the Migrator deletes each one under
        // this extension's ID after its down() runs. Relabelling them here, part
        // way through a rollback, left the remaining rows behind as orphans.
        $schema->rename('extension_manager_tasks', 'package_manager_tasks');
    }
];
