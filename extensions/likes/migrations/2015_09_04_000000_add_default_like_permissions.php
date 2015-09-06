<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Flarum\Migrations\Migration;
use Flarum\Core\Groups\Group;
use Flarum\Core\Groups\Permission;

class AddDefaultLikePermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Permission::unguard();

        $permission = Permission::firstOrNew($this->getPermissionAttributes());

        $permission->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::where($this->getPermissionAttributes())->delete();
    }

    protected function getPermissionAttributes()
    {
        return [
            'group_id' => Group::MEMBER_ID,
            'permission' => 'discussion.likePosts'
        ];
    }
}
