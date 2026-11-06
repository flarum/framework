<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

/*
 * One row per voter per idea. The composite primary key is what makes a vote
 * idempotent: a second vote from the same member is an ignored insert, so a
 * double click or a retried request can never count twice.
 */
return Migration::createTable('ideation_votes', function (Blueprint $table) {
    $table->unsignedInteger('discussion_id');
    $table->unsignedInteger('user_id');
    $table->dateTime('created_at')->nullable();

    $table->primary(['discussion_id', 'user_id']);
    $table->index('user_id');

    $table->foreign('discussion_id')->references('id')->on('discussions')->cascadeOnDelete();
    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
});
