<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->renameColumn('id', 'token');
            $table->renameColumn('lifetime', 'lifetime_seconds');
            $table->renameColumn('last_activity', 'last_activity_at');
            $table->dateTime('created_at');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->renameColumn('lifetime_seconds', 'lifetime');
            $table->renameColumn('last_activity_at', 'last_activity');
            $table->dropColumn('created_at');
            $table->renameColumn('token', 'id');
        });
    }
];
