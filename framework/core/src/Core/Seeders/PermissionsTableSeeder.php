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
            ['group.2' , 'forum'          , 'view'],
            ['group.2' , 'forum'          , 'register'],

            // Members can create and reply to discussions + edit their own stuff
            ['group.3' , 'forum'          , 'startDiscussion'],
            ['group.3' , 'discussion'     , 'editOwn'],
            ['group.3' , 'discussion'     , 'reply'],
            ['group.3' , 'post'           , 'editOwn'],

            // Moderators can edit + delete stuff and suspend users
            ['group.4' , 'discussion'     , 'delete'],
            ['group.4' , 'discussion'     , 'edit'],
            ['group.4' , 'post'           , 'delete'],
            ['group.4' , 'post'           , 'edit'],
            ['group.4' , 'user'           , 'suspend'],

        ];
        foreach ($permissions as &$permission) {
            $permission = [
                'grantee'    => $permission[0],
                'entity'     => $permission[1],
                'permission' => $permission[2]
            ];
        }
        Permission::insert($permissions);
	}

}
