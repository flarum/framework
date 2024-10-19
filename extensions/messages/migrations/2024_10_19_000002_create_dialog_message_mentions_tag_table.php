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
    'dialog_message_mentions_tag',
    function (Blueprint $table, Builder $schema) {
        $table->unsignedInteger('dialog_message_id');
        $table->unsignedInteger('mentions_tag_id');
        $table->dateTime('created_at')->nullable()->useCurrent();

        $table->primary(['dialog_message_id', 'mentions_tag_id']);
        $table->foreign('dialog_message_id')->references('id')->on('dialog_messages')->cascadeOnDelete();

        if ($schema->hasTable('tags')) {
            $table->foreign('mentions_tag_id')->references('id')->on('tags')->cascadeOnDelete();
        }
    }
);
