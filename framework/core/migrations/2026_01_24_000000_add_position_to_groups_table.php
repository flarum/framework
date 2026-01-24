<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('groups', function (Blueprint $table) {
            $table->integer('position')->after('is_hidden')->nullable();
        });

        $db = $schema->getConnection();

        $ids = $db->table('groups')
            ->orderBy('id')
            ->pluck('id');

        $position = 0;
        foreach ($ids as $id) {
            $db->table('groups')
                ->where('id', $id)
                ->update(['position' => $position]);

            $position++;
        }
    },

    'down' => function (Builder $schema) {
        $schema->table('groups', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
];
