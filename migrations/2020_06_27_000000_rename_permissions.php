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

        $db->table('group_permission')->where('permission', 'viewDiscussions')->update(['permission' => 'viewForum']);
    },

    'down' => function (Builder $schema) {
        $db = $schema->getConnection();

        $db->table('group_permission')->where('permission', 'viewForum')->update(['permission' => 'viewDiscussions']);
    }
];
