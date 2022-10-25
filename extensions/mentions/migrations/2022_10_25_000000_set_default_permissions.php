<?php

use Flarum\Database\Migration;
use Flarum\Group\Group;

return Migration::addPermissions([
    'searchGroups' => Group::MEMBER_ID,
]);
