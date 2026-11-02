<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable('oauth_provider_access_tokens', function (Blueprint $table) {
    $table->string('id', 100)->primary();
    $table->string('client_id', 255);
    $table->integer('user_id')->unsigned();
    $table->string('scopes', 1000)->nullable();
    $table->string('resource', 2048)->nullable();
    // The authorization code the token descends from, through every refresh.
    $table->string('grant_id', 100)->nullable();
    $table->boolean('revoked')->default(false);
    $table->dateTime('expires_at');
    $table->dateTime('last_used_at')->nullable();
    $table->dateTime('created_at')->nullable();

    $table->foreign('client_id')->references('id')->on('oauth_provider_clients')->onDelete('cascade');
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    $table->index('expires_at');
    $table->index('grant_id');
});
