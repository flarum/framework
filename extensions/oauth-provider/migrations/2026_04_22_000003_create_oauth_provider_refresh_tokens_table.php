<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable('oauth_provider_refresh_tokens', function (Blueprint $table) {
    $table->string('id', 100)->primary();
    $table->string('access_token_id', 100);
    $table->boolean('revoked')->default(false);
    $table->dateTime('expires_at');
    $table->dateTime('created_at')->nullable();

    $table->foreign('access_token_id')
        ->references('id')
        ->on('oauth_provider_access_tokens')
        ->onDelete('cascade');
    $table->index('expires_at');
});
