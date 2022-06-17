<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Builder;

return [
    'up' => static function (Builder $schema) {
        $db = $schema->getConnection();

        $db->table('group_permission')
            ->where('permission', 'LIKE', '%viewDiscussions')
            ->update(['permission' => $db->raw("REPLACE(permission,  'viewDiscussions', 'viewForum')")]);

        $db->table('group_permission')
            ->where('permission', 'viewUserList')
            ->update(['permission' => 'searchUsers']);
    },

    'down' => static function (Builder $schema) {
        $db = $schema->getConnection();

        $db->table('group_permission')
            ->where('permission', 'LIKE', '%viewForum')
            ->update(['permission' => $db->raw("REPLACE(permission,  'viewForum', 'viewDiscussions')")]);

        $db->table('group_permission')
            ->where('permission', 'searchUsers')
            ->update(['permission' => 'viewUserList']);
    }
];
