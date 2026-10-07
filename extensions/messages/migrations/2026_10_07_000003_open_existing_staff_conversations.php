<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Group\Group;
use Illuminate\Database\Query\Builder as Query;
use Illuminate\Database\Schema\Builder;

// Conversations that someone allowed to message users without messaging
// permission had already written in, from before conversations could be
// opened to replies: open them, as sending a message now would. That is an
// admin, or a member of a group granted the permission when this runs.
return [
    'up' => function (Builder $schema) {
        $schema->getConnection()->table('dialogs')
            ->whereIn('id', function (Query $query) {
                $query->select('dialog_id')
                    ->from('dialog_messages')
                    ->whereIn('user_id', function (Query $query) {
                        $query->select('user_id')
                            ->from('group_user')
                            ->where('group_id', Group::ADMINISTRATOR_ID)
                            ->orWhereIn('group_id', function (Query $query) {
                                $query->select('group_id')
                                    ->from('group_permission')
                                    ->where('permission', 'dialog.messageUsersWithoutPermission');
                            });
                    });
            })
            ->update(['anyone_can_reply' => true]);
    },

    'down' => function (Builder $schema) {
        // Nothing to put back: the column's own migration drops it.
    },
];
