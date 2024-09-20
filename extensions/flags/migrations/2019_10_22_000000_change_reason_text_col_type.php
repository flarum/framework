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
        $schema->table('flags', function (Blueprint $table) {
            $table->text('reason_detail')->nullable()->change();
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('flags', function (Blueprint $table) {
            $table->string('reason_detail')->nullable()->change();
        });
    }
];
