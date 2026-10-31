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
            $table->boolean('has_avatar_2x')->default(false)->after('avatar_url');
            $table->boolean('has_avatar_3x')->default(false)->after('has_avatar_2x');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('users', function (Blueprint $table) {
            $table->dropColumn(['has_avatar_2x', 'has_avatar_3x']);
        });
    }
];
