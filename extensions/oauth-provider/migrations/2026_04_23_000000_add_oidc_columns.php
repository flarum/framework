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
        $schema->table('oauth_provider_auth_codes', function (Blueprint $table) {
            $table->string('nonce', 255)->nullable()->after('scopes');
            $table->dateTime('auth_time')->nullable()->after('nonce');
        });
        $schema->table('oauth_provider_access_tokens', function (Blueprint $table) {
            $table->string('nonce', 255)->nullable()->after('scopes');
            $table->dateTime('auth_time')->nullable()->after('nonce');
        });
    },
    'down' => function (Builder $schema) {
        $schema->table('oauth_provider_auth_codes', function (Blueprint $table) {
            $table->dropColumn(['nonce', 'auth_time']);
        });
        $schema->table('oauth_provider_access_tokens', function (Blueprint $table) {
            $table->dropColumn(['nonce', 'auth_time']);
        });
    },
];
