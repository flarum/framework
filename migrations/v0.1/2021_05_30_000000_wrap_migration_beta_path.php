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
        $db = $schema->getConnection();

        $db->table('migrations')
            ->whereNull('extension')
            ->update(['migration' => $db->raw("CONCAT('v0.1/', migration)")]);
    },

    'down' => function (Builder $schema) {
        $db = $schema->getConnection();

        $db->table('migrations')
            ->where('permission', 'LIKE', 'viewForum')
            ->update(['permission' => $db->raw("REPLACE(migration,  'v0.1/', '')")]);
    }
];
