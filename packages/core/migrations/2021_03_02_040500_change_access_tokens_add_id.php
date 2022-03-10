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
        $schema->table('access_tokens', function (Blueprint $table) {
            // Replace primary key with unique index so we can create a new primary
            $table->dropPrimary('token');
            $table->unique('token');
        });

        // This needs to be done in a second statement because of the order Laravel runs operations in
        $schema->table('access_tokens', function (Blueprint $table) {
            // Introduce new increment-based ID
            $table->increments('id')->first();
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->dropColumn('id');
            $table->dropIndex('token');
            $table->primary('token');
        });
    }
];
