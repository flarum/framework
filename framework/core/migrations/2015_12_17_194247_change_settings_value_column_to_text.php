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
    'up' => static function (Builder $schema) {
        $schema->table('settings', static function (Blueprint $table) {
            $table->text('value')->change();
        });
    },

    'down' => static function (Builder $schema) {
        $schema->table('settings', static function (Blueprint $table) {
            $table->binary('value')->change();
        });
    }
];
