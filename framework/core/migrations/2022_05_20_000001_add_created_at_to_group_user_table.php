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
        $schema->table('group_user', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable();
        });

        // do this manually because dbal doesn't recognize timestamp columns
        $connection = $schema->getConnection();
        $prefix = $connection->getTablePrefix();
        $connection->statement("ALTER TABLE `${prefix}group_user` MODIFY created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
    },

    'down' => function (Builder $schema) {
        $schema->table('group_user', function (Blueprint $table) {
            $table->dropColumn('created_at');
        });
    }
];
