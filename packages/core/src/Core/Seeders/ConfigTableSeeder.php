<?php  namespace Flarum\Core\Seeders;

use Illuminate\Database\Seeder;

class ConfigTableSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $config = [
            'api_url'               => 'http://flarum.dev/api.php',
            'base_url'              => 'http://flarum.dev/index.php',
            'forum_title'           => 'Flarum Demo Forum',
            'welcome_message'       => 'Flarum is now at a point where you can have basic conversations, so here is a little demo for you to break.',
            'welcome_title'         => 'Welcome to Flarum Demo Forum',
            'extensions_enabled'    => '[]',
            'locale'                => 'en',
            'theme_primary_color'   => '#536F90',
            'theme_secondary_color' => '#536F90',
            'theme_dark_mode'       => false,
            'theme_colored_header'  => false,
        ];

        app('db')->table('config')->insert(array_map(function ($key, $value) {
            return compact('key', 'value');
        }, array_keys($config), $config));
    }
}
