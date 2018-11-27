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
        $schema->table('discussions', function (Blueprint $table) use ($schema) {
            $table->index(['is_sticky', 'created_at']);

            Migration::fixIndexNames($schema, $table);
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('discussions', function (Blueprint $table) use ($schema) {
            $table->dropIndex(['is_sticky', 'created_at']);

            Migration::fixIndexNames($schema, $table);
        });
    }
];
