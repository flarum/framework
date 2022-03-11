<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Flarum\Group\Group;

return Migration::addPermissions([
    'discussion.startWithoutApproval' => Group::MEMBER_ID,
    'discussion.replyWithoutApproval' => Group::MEMBER_ID,
    'discussion.approvePosts' => Group::MODERATOR_ID
]);
