<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Flarum\Group\Group;

return Migration::addPermissions([
    // Guests can view the forum
    'viewDiscussions' => Group::GUEST_ID,

    // Members can create and reply to discussions, and view the user list
    'startDiscussion' => Group::MEMBER_ID,
    'discussion.reply' => Group::MEMBER_ID,
    'viewUserList' => Group::MEMBER_ID,

    // Moderators can edit + delete stuff
    'discussion.hide' => Group::MODERATOR_ID,
    'discussion.editPosts' => Group::MODERATOR_ID,
    'discussion.hidePosts' => Group::MODERATOR_ID,
    'discussion.rename' => Group::MODERATOR_ID,
    'discussion.viewIpsPosts' => Group::MODERATOR_ID,
]);
