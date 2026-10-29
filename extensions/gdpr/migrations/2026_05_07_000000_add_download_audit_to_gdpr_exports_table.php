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
        $schema->table('gdpr_exports', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('gdpr_exports', 'downloaded_at')) {
                $table->dateTime('downloaded_at')->nullable();
            }
            if (! $schema->hasColumn('gdpr_exports', 'downloaded_ip')) {
                $table->string('downloaded_ip', 45)->nullable();
            }
            if (! $schema->hasColumn('gdpr_exports', 'downloaded_user_agent')) {
                $table->string('downloaded_user_agent', 255)->nullable();
            }
        });
    },
    'down' => function (Builder $schema) {
        $schema->table('gdpr_exports', function (Blueprint $table) use ($schema) {
            foreach (['downloaded_at', 'downloaded_ip', 'downloaded_user_agent'] as $column) {
                if ($schema->hasColumn('gdpr_exports', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    },
];
