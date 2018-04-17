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
        $schema->table('registration_tokens', function (Blueprint $table) {
            $table->renameColumn('id', 'token');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('registration_tokens', function (Blueprint $table) {
            $table->renameColumn('token', 'id');
        });
    }
];
