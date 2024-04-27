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
            $table->dropColumn('created_at', 'expires_at');
        });

        $schema->table('access_tokens', function (Blueprint $table) {
            $table->string('id', 40)->change();
            $table->integer('last_activity');
            $table->integer('lifetime');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->string('id', 100)->change();
            $table->dropColumn('last_activity', 'lifetime');
            $table->timestamp('created_at');
            $table->timestamp('expires_at');
        });
    }
];
