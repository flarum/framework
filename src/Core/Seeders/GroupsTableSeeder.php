<?php  namespace Flarum\Core\Seeders;

use Illuminate\Database\Seeder;
use Flarum\Core\Models\Group;

class GroupsTableSeeder extends Seeder {

	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
        Group::unguard();
        Group::truncate();

		$groups = ['Administrator', 'Guest', 'Member', 'Moderator', 'Staff'];
        foreach ($groups as $group) {
            Group::create(['name' => $group]);
        }
	}

}
