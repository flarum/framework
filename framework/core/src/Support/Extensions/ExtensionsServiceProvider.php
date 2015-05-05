<?php namespace Flarum\Support\Extensions;

use Illuminate\Support\ServiceProvider;
use DB;

class ExtensionsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $app = $this->app;
        $extensions = json_decode(DB::table('config')->where('key', 'extensions_enabled')->pluck('value'), true);

        foreach ($extensions as $extension) {
            if (file_exists($file = base_path().'/extensions/'.$extension.'/bootstrap.php')) {
                require $file;
            }
        }
    }
}
