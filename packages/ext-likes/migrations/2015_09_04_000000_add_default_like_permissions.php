<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Likes\Migration;

use Flarum\Core\Group;
use Flarum\Core\Permission;
use Flarum\Database\AbstractMigration;

class AddDefaultLikePermissions extends AbstractMigration
{
    public function up()
    {
        Permission::unguard();

        $permission = Permission::firstOrNew($this->getPermissionAttributes());

        $permission->save();
    }

    public function down()
    {
        Permission::where($this->getPermissionAttributes())->delete();
    }

    /**
     * @return array
     */
    protected function getPermissionAttributes()
    {
        return [
            'group_id' => Group::MEMBER_ID,
            'permission' => 'discussion.likePosts'
        ];
    }
}
