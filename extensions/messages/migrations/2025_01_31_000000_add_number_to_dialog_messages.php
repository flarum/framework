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
        $schema->table('dialog_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('number')->nullable()->after('content');
        });

        $numbers = [];

        $schema->getConnection()
            ->table('dialogs')
            ->orderBy('id')
            ->each(function (object $dialog) use ($schema, &$numbers) {
                $numbers[$dialog->id] = 0;

                $schema->getConnection()
                    ->table('dialog_messages')
                    ->where('dialog_id', $dialog->id)
                    ->orderBy('id')
                    ->each(function (object $message) use ($schema, &$numbers) {
                        $schema->getConnection()
                            ->table('dialog_messages')
                            ->where('id', $message->id)
                            ->update(['number' => ++$numbers[$message->dialog_id]]);
                    });

                unset($numbers[$dialog->id]);
            });

        $schema->table('dialog_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('number')->nullable(false)->change();
        });
    },
    'down' => function (Builder $schema) {
        $schema->table('dialog_messages', function (Blueprint $table) {
            $table->dropColumn('number');
        });
    }
];
