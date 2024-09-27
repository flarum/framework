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
        $schema->table('dialogs', function (Blueprint $table) {
            $table->foreign('first_message_id')->references('id')->on('dialog_messages')->nullOnDelete();
            $table->foreign('last_message_id')->references('id')->on('dialog_messages')->nullOnDelete();
        });
    },
    'down' => function (Builder $schema) {
        $schema->table('dialogs', function (Blueprint $table) {
            $table->dropForeign(['first_message_id']);
            $table->dropForeign(['last_message_id']);
        });
    }
];
