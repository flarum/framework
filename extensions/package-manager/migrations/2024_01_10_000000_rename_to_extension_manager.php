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
        $schema->rename('extension_manager_tasks', 'package_manager_tasks');
        $schema->getConnection()->table('migrations')->where('extension', 'flarum-extension-manager')->update([
            'extension' => 'flarum-package-manager',
        ]);
    }
];
