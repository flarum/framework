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
            $table->timestamp('email_bounced_at')->nullable()->after('is_email_confirmed');
            $table->string('email_bounce_reason')->nullable()->after('email_bounced_at');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('users', function (Blueprint $table) {
            $table->dropColumn(['email_bounced_at', 'email_bounce_reason']);
        });
    },
];
