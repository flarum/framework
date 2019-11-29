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
        $schema->table('tags', function (Blueprint $table) {
            $table->string('slug', 100)->change();
            $table->unique('slug');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('tags', function (Blueprint $table) {
            $table->string('slug', 255)->change();
            $table->dropUnique(['slug']);
        });
    }
];
