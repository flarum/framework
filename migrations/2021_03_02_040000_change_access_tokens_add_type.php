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
            $table->string('type', 100)->index();
        });

        // Since all active sessions will stop working on update due to switching from user_id to access_token
        // We can do things simple here by terminating all tokens that have the previously default lifetime
        $schema->getConnection()->table('access_tokens')
            ->where('lifetime_seconds', 3600)
            ->delete();

        // We will then assume that all remaining tokens are remember tokens
        // This will include tokens that previously had a custom lifetime
        $schema->getConnection()->table('access_tokens')
            ->update([
                'type' => 'session_remember',
            ]);

        $schema->table('access_tokens', function (Blueprint $table) {
            $table->dropColumn('lifetime_seconds');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->integer('lifetime_seconds');
        });
    }
];
