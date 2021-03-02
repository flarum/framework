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
            $table->unsignedInteger('id')->first();
        });

        $tokens = $schema->getConnection()->table('access_tokens')
            ->cursor();

        // Insert initial value for our new primary key
        // This migration runs after the "add type" migration, this ensures we have a minimal number of tokens remaining
        foreach ($tokens as $i => $token) {
            $schema->getConnection()->table('access_tokens')
                ->where('token', $token->token)
                ->update([
                    'id' => $i + 1
                ]);
        }

        $schema->table('access_tokens', function (Blueprint $table) {
            $table->dropPrimary('token');
            $table->unique('token');
            $table->primary('id');
        });

        // This needs to be done in a second statement because of the order Laravel runs operations in
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->increments('id')->change();
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
