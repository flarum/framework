<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/**
 * Two constraints the code allocating these rows can race on, so the schema
 * has to hold them: a member is in a dialog once, and a dialog's message
 * numbers are unique. Existing data is tidied first so the indexes can go on,
 * and dialogs left pointing at nothing (an earlier bug) are repaired.
 */
return [
    'up' => function (Builder $schema) {
        $db = $schema->getConnection();

        // A member in a dialog twice: keep their first row.
        $db->table('dialog_user')
            ->select('dialog_id', 'user_id', $db->raw('MIN(id) as keep'))
            ->groupBy('dialog_id', 'user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->each(function (object $row) use ($db) {
                $db->table('dialog_user')
                    ->where('dialog_id', $row->dialog_id)
                    ->where('user_id', $row->user_id)
                    ->where('id', '!=', $row->keep)
                    ->delete();
            });

        // Two messages with one number: renumber that dialog in order.
        $db->table('dialog_messages')
            ->select('dialog_id')
            ->groupBy('dialog_id', 'number')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('dialog_id')
            ->unique()
            ->each(function (int $dialogId) use ($db) {
                $number = 0;

                $db->table('dialog_messages')
                    ->where('dialog_id', $dialogId)
                    ->orderBy('id')
                    ->pluck('id')
                    ->each(function (int $id) use ($db, &$number) {
                        $db->table('dialog_messages')->where('id', $id)->update(['number' => ++$number]);
                    });
            });

        // Dialogs without a first or last message pointer: point them at what
        // they hold; those with nothing in them go.
        $db->table('dialogs')
            ->whereNull('first_message_id')
            ->orWhereNull('last_message_id')
            ->pluck('id')
            ->each(function (int $dialogId) use ($db) {
                $first = $db->table('dialog_messages')->where('dialog_id', $dialogId)->orderBy('id')->first();
                $last = $db->table('dialog_messages')->where('dialog_id', $dialogId)->orderByDesc('id')->first();

                if (! $first) {
                    $db->table('dialogs')->where('id', $dialogId)->delete();

                    return;
                }

                $db->table('dialogs')->where('id', $dialogId)->update([
                    'first_message_id' => $first->id,
                    'created_at' => $first->created_at,
                    'last_message_id' => $last->id,
                    'last_message_at' => $last->created_at,
                    'last_message_user_id' => $last->user_id,
                ]);
            });

        $schema->table('dialog_user', function (Blueprint $table) {
            $table->unique(['dialog_id', 'user_id']);
            // What every unread count and visibility check filters on.
            $table->index(['user_id', 'last_read_message_id']);
        });

        $schema->table('dialog_messages', function (Blueprint $table) {
            // Also the index the message list sorts on.
            $table->unique(['dialog_id', 'number']);
        });
    },
    'down' => function (Builder $schema) {
        $schema->table('dialog_messages', function (Blueprint $table) {
            $table->dropUnique(['dialog_id', 'number']);
        });

        $schema->table('dialog_user', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'last_read_message_id']);
            $table->dropUnique(['dialog_id', 'user_id']);
        });
    },
];
