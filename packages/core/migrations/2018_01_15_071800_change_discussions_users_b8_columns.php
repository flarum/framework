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
        $schema->table('discussions_users', function (Blueprint $table) {
            $table->renameColumn('read_time', 'last_read_at');
            $table->renameColumn('read_number', 'last_read_post_number');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('discussions_users', function (Blueprint $table) {
            $table->renameColumn('last_read_at', 'read_time');
            $table->renameColumn('last_read_post_number', 'read_number');
        });
    }
];
