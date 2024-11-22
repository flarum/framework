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
        $schema->table('users', function (Blueprint $table) {
            $table->string('email', 254)->change();
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('users', function (Blueprint $table) {
            $table->string('email', 150)->change();
        });
    }
];
