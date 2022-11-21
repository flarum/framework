<?php

use Flarum\Database\Migration;
use Flarum\Group\Group;

return Migration::addPermissions([
    'mentionTags' => [Group::MEMBER_ID],
]);
