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
        if (! $schema->hasColumn('gdpr_erasure', 'cancelled_at')) {
            $schema->table('gdpr_erasure', function (Blueprint $table) {
                $table->dateTime('cancelled_at')->nullable();
            });
        }
    },
    'down' => function (Builder $schema) {
        if ($schema->hasColumn('gdpr_erasure', 'cancelled_at')) {
            $schema->table('gdpr_erasure', function (Blueprint $table) {
                $table->dropColumn('cancelled_at');
            });
        }
    },
];
