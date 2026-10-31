<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable('oauth_provider_clients', function (Blueprint $table) {
    $table->string('id', 255)->primary();
    $table->string('name');
    $table->string('secret', 255)->nullable();
    $table->text('redirect_uris');
    $table->string('scopes', 1000)->nullable();
    $table->boolean('confidential')->default(true);
    $table->boolean('revoked')->default(false);
    $table->string('source', 32)->default('admin');
    $table->string('logo_uri', 2048)->nullable();
    $table->string('client_uri', 2048)->nullable();
    $table->dateTime('metadata_expires_at')->nullable();
    $table->integer('created_by')->unsigned()->nullable();
    $table->dateTime('created_at')->nullable();
    $table->dateTime('updated_at')->nullable();

    $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
});
