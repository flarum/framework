<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

$tables = [
    'dialog_message_mentions_user' => ['message_mentions_user', ['dialog_message_id' => 'message_id', 'mentions_user_id' => 'user_id']],
    'dialog_message_mentions_post' => ['message_mentions_post', ['dialog_message_id' => 'message_id', 'mentions_post_id' => 'post_id']],
    'dialog_message_mentions_group' => ['message_mentions_group', ['dialog_message_id' => 'message_id', 'mentions_group_id' => 'group_id']],
    'dialog_message_mentions_tag' => ['message_mentions_tag', ['dialog_message_id' => 'message_id', 'mentions_tag_id' => 'tag_id']],
];

return [
    'up' => function (Builder $schema) use ($tables) {
        foreach ($tables as $from => [$to, $columns]) {
            if (! $schema->hasTable($from) || $schema->hasTable($to)) {
                continue;
            }

            $schema->rename($from, $to);

            $schema->table($to, function (Blueprint $table) use ($columns) {
                foreach ($columns as $old => $new) {
                    $table->renameColumn($old, $new);
                }
            });
        }
    },
    // Deliberately one-way. The migrations that created these tables drop them by their
    // current name, and they roll back after this one, so restoring the old names here
    // would leave that drop with nothing to remove.
    'down' => function (Builder $schema) {
    },
];
