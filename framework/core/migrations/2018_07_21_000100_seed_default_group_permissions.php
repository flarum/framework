<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Group\Group;
use Illuminate\Database\Schema\Builder;

$rows = [
    // Guests can view the forum
    ['permission' => 'viewDiscussions', 'group_id' => Group::GUEST_ID],

    // Members can create and reply to discussions, and view the user list
    ['permission' => 'startDiscussion', 'group_id' => Group::MEMBER_ID],
    ['permission' => 'discussion.reply', 'group_id' => Group::MEMBER_ID],
    ['permission' => 'viewUserList', 'group_id' => Group::MEMBER_ID],

    // Moderators can edit + delete stuff
    ['permission' => 'discussion.hide', 'group_id' => Group::MODERATOR_ID],
    ['permission' => 'discussion.editPosts', 'group_id' => Group::MODERATOR_ID],
    ['permission' => 'discussion.hidePosts', 'group_id' => Group::MODERATOR_ID],
    ['permission' => 'discussion.rename', 'group_id' => Group::MODERATOR_ID],
    ['permission' => 'discussion.viewIpsPosts', 'group_id' => Group::MODERATOR_ID],
    ['permission' => 'user.viewLastSeenAt', 'group_id' => Group::MODERATOR_ID],
];

return [
    'up' => function (Builder $schema) use ($rows) {
        $db = $schema->getConnection();

        foreach ($rows as $row) {
            if ($db->table('groups')->where('id', $row['group_id'])->doesntExist()) {
                continue;
            }

            if ($db->table('group_permission')->where($row)->doesntExist()) {
                $db->table('group_permission')->insert($row);
            }
        }
    },

    'down' => function (Builder $schema) use ($rows) {
        $db = $schema->getConnection();

        foreach ($rows as $row) {
            $db->table('group_permission')->where($row)->delete();
        }
    }
];
