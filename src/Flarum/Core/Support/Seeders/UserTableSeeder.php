<?php namespace Flarum\Core\Support\Seeders;

use Illuminate\Database\Seeder;
use DB;

use Flarum\Core\Users\User;
use Flarum\Core\Groups\Group;

class UserTableSeeder extends Seeder
{
    /**
	 * Run the database seeds.
	 *
	 * @return void
	 */
    public function run()
    {
        $faker = Faker\Factory::create();

        $groups = ['Administrator', 'Guest', 'Member', 'Moderator', 'Staff'];
        foreach ($groups as $group) {
            Group::create(['name' => $group]);
        }

        for ($i = 0; $i < 100; $i++) {
            $user = User::create([
                'username'  => $faker->userName,
                'email'     => $faker->safeEmail,
                'password'  => 'password',
                'join_time' => $faker->dateTimeThisYear,
                'time_zone' => $faker->timezone
            ]);

            // Assign the users to the 'Member' group, and possibly some others.
            $user->groups()->attach(3);
            if (rand(1, 50) == 1) {
                $user->groups()->attach(4);
            }
            if (rand(1, 20) == 1) {
                $user->groups()->attach(5);
            }
            if (rand(1, 20) == 1) {
                $user->groups()->attach(1);
            }
        }

        // Set up the default permissions.
        $permissions = [

            // Guests can view the forum
            ['group.2' , 'forum'          , 'view'],

            // Members can create and reply to discussions + edit their own stuff
            ['group.3' , 'discussion'     , 'create'],
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
        DB::table('permissions')->insert($permissions);
    }
}
