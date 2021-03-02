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
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->string('type')->index();
        });

        $tokens = $schema->getConnection()->table('access_tokens')
            ->cursor();

        foreach ($tokens as $i => $token) {
            // This is the value that was hard-coded for remember tokens
            // Everything else will default to normal session, even if it was customized
            $isRemember = $token->lifetime_seconds === 5 * 365 * 24 * 60 * 60;

            $schema->getConnection()->table('access_tokens')
                ->where('token', $token->token)
                ->update([
                    'type' => $isRemember ? 'session_remember' : 'session',
                ]);
        }

        $schema->table('access_tokens', function (Blueprint $table) {
            $table->dropColumn('lifetime_seconds');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->integer('lifetime_seconds');
        });
    }
];
