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
        $schema->table('tags', function (Blueprint $table) {
            $table->boolean('is_primary')->default(false)->after('background_mode');
        });

        $schema->getConnection()
            ->table('tags')
            ->whereNotNull('position')
            ->update(['is_primary' => true]);
    },
    'down' => function (Builder $schema) {
        $schema->table('tags', function (Blueprint $table) {
            $table->dropColumn('is_primary');
        });
    }
];
