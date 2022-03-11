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
        $groups = $schema->getConnection()->table('groups')
            ->where('icon', '<>', '')
            ->where('icon', 'NOT LIKE', '%fa-%')
            ->select('id', 'icon')
            ->cursor();

        foreach ($groups as $group) {
            $schema->getConnection()->table('groups')
                ->where('id', $group->id)
                ->update([
                    'icon' => 'fas fa-'.$group->icon
                ]);
        }
    },

    'down' => function (Builder $schema) {
        //
    }
];
