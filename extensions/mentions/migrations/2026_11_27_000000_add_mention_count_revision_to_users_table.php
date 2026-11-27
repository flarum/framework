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
    // Raised by one with every write of mention_count, in the same UPDATE, so
    // a client holding two copies of a user's count can tell which is newer
    // whatever order the responses arrived in. Existing rows start at 0; the
    // first recount moves them to 1.
    'up' => function (Builder $schema) {
        $schema->table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('mention_count_revision')->default(0);
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('users', function (Blueprint $table) {
            $table->dropColumn('mention_count_revision');
        });
    }
];
