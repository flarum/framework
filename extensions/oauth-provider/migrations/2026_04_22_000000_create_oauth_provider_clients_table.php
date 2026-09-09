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
    $table->string('id', 80)->primary();
    $table->string('name');
    $table->string('secret', 255)->nullable();
    $table->text('redirect_uris');
    $table->string('scopes', 1000)->nullable();
    $table->boolean('confidential')->default(true);
    $table->boolean('revoked')->default(false);
    $table->integer('created_by')->unsigned()->nullable();
    $table->dateTime('created_at')->nullable();
    $table->dateTime('updated_at')->nullable();

    $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
});
