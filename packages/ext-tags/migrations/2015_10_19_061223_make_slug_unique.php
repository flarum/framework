<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('tags', function (Blueprint $table) use ($schema) {
            $table->string('slug', 100)->change();
            $table->unique('slug');

            Migration::fixIndexNames($schema, $table);
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('tags', function (Blueprint $table) use ($schema) {
            $table->string('slug', 255)->change();
            $table->dropUnique(['slug']);

            Migration::fixIndexNames($schema, $table);
        });
    }
];
