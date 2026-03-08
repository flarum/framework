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
        if (! $schema->hasColumn('gdpr_erasure', 'confirmation_ip')) {
            $schema->table('gdpr_erasure', function (Blueprint $table) {
                $table->string('confirmation_ip', 45)->nullable();
            });
        }
    },
    'down' => function (Builder $schema) {
        if ($schema->hasColumn('gdpr_erasure', 'confirmation_ip')) {
            $schema->table('gdpr_erasure', function (Blueprint $table) {
                $table->dropColumn('confirmation_ip');
            });
        }
    },
];
