<?php  namespace Flarum\Core\Seeders;

use Illuminate\Database\Seeder;
use Flarum\Core\Models\Permission;

class PermissionsTableSeeder extends Seeder {

	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
        Permission::truncate();

		$permissions = [

            // Guests can view the forum
            [2, 'forum.view'],

            // Members can create and reply to discussions + edit their own stuff
            [3, 'forum.startDiscussion'],
            [3, 'discussion.reply'],

            // Moderators can edit + delete stuff and suspend users
            [4, 'discussion.delete'],
            [4, 'discussion.rename'],
            [4, 'post.delete'],
            [4, 'post.edit'],
            [4, 'user.suspend'],

        ];
        foreach ($permissions as &$permission) {
            $permission = [
                'group_id'   => $permission[0],
                'permission' => $permission[1]
            ];
        }
        Permission::insert($permissions);
	}

}
