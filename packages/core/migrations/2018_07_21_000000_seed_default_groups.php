<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Group\Group;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $db = $schema->getConnection();

        $groups = [
            [Group::ADMINISTRATOR_ID, 'Admin', 'Admins', '#B72A2A', 'fas fa-wrench'],
            [Group::GUEST_ID, 'Guest', 'Guests', null, null],
            [Group::MEMBER_ID, 'Member', 'Members', null, null],
            [Group::MODERATOR_ID, 'Mod', 'Mods', '#80349E', 'fas fa-bolt']
        ];

        foreach ($groups as $group) {
            if ($db->table('groups')->where('id', $group[0])->exists()) {
                continue;
            }

            $db->table('groups')->insert(array_combine(['id', 'name_singular', 'name_plural', 'color', 'icon'], $group));
        }
    },

    'down' => function (Builder $schema) {
        // do nothing so as to preserve user data
    }
];
