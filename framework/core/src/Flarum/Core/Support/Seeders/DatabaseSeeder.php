<?php namespace Flarum\Core\Support\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {

	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		Eloquent::unguard();

		$tables = [
			'config',
			'discussions',
			'groups',
			'permissions',
			'posts',
			'sessions',
			'users',
			'users_discussions',
			'users_groups'
		];

		foreach ($tables as $table) 
		{ 
			DB::table($table)->truncate();
		}

		$this->call('Flarum\Core\Support\Seeders\ConfigTableSeeder');
		$this->call('Flarum\Core\Support\Seeders\UserTableSeeder');
		$this->call('Flarum\Core\Support\Seeders\DiscussionTableSeeder');
	}

}
