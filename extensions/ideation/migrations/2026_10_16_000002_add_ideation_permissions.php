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
    'ideation.postIdeas' => Group::MEMBER_ID,
    'discussion.ideationVote' => Group::MEMBER_ID,
    'discussion.ideationModerate' => Group::MODERATOR_ID,
]);
