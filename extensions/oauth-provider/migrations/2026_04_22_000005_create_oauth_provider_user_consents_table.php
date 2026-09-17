<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable('oauth_provider_user_consents', function (Blueprint $table) {
    $table->increments('id');
    $table->integer('user_id')->unsigned();
    $table->string('client_id', 80);
    $table->string('scopes', 1000)->nullable();
    $table->boolean('revoked')->default(false);
    $table->dateTime('created_at')->nullable();
    $table->dateTime('updated_at')->nullable();

    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    $table->foreign('client_id')->references('id')->on('oauth_provider_clients')->onDelete('cascade');
    $table->unique(['user_id', 'client_id']);
});
