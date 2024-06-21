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
        $schema->table('post_mentions_post', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable();
        });

        $schema->table('post_mentions_post', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable()->useCurrent()->change();
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('post_mentions_post', function (Blueprint $table) {
            $table->dropColumn('created_at');
        });
    }
];
