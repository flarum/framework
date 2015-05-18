<?php  namespace Flarum\Core\Seeders;

use Illuminate\Database\Seeder;
use Flarum\Core\Models\Group;

class GroupsTableSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Group::unguard();
        Group::truncate();

        $groups = [
            ['Admin', 'Admins', '#B72A2A', 'wrench'],
            ['Guest', 'Guests', null, null],
            ['Member', 'Members', null, null],
            ['Mod', 'Mods', '#80349E', 'bolt']
        ];
        foreach ($groups as $group) {
            Group::create([
                'name_singular' => $group[0],
                'name_plural' => $group[1],
                'color' => $group[2],
                'icon' => $group[3]
            ]);
        }
    }
}
