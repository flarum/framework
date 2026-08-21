<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return Migration::createTable(
    'message_mentions_tag',
    function (Blueprint $table, Builder $schema) {
        $table->unsignedInteger('message_id');
        $table->unsignedInteger('tag_id');
        $table->dateTime('created_at')->nullable()->useCurrent();

        $table->primary(['message_id', 'tag_id']);
        $table->foreign('message_id')->references('id')->on('dialog_messages')->cascadeOnDelete();

        if ($schema->hasTable('tags')) {
            $table->foreign('tag_id')->references('id')->on('tags')->cascadeOnDelete();
        }
    }
);
