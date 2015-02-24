<?php namespace Flarum\Core\Seeders;

use Illuminate\Database\Seeder;
use Flarum\Core\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
	 * Run the database seeds.
	 *
	 * @return void
	 */
    public function run()
    {
        User::unguard();

        $faker = \Faker\Factory::create();

        for ($i = 0; $i < 100; $i++) {
            $user = User::create([
                'username'     => $faker->userName,
                'email'        => $faker->safeEmail,
                'is_confirmed' => true,
                'is_activated' => true,
                'password'     => 'password',
                'join_time'    => $faker->dateTimeThisYear
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
    }
}
