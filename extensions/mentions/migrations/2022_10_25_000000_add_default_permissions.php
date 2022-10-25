<?php

use Flarum\Database\Migration;
use Flarum\Group\Group;

return Migration::addPermissions([
    'mentionGroups' => Group::MEMBER_ID
]);
