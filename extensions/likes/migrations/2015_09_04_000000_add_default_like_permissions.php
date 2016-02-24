<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Core\Group;
use Flarum\Core\Permission;

$getPermissionAttributes = function () {
    return [
        'group_id' => Group::MEMBER_ID,
        'permission' => 'discussion.likePosts',
    ];
};

return [
    'up' => function () use ($getPermissionAttributes) {
        Permission::unguard();

        $permission = Permission::firstOrNew($getPermissionAttributes());

        $permission->save();
    },

    'down' => function () use ($getPermissionAttributes) {

        Permission::where($getPermissionAttributes())->delete();
    }
];
