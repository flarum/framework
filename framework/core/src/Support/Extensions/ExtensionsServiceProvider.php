<?php namespace Flarum\Support\Extensions;

use Illuminate\Support\ServiceProvider;
use DB;

class ExtensionsServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $app = $this->app;
        $extensions = json_decode(DB::table('config')->where('key', 'extensions_enabled')->pluck('value'), true);
        $providers = [];

        foreach ($extensions as $extension) {
            if (file_exists($file = base_path().'/extensions/'.$extension.'/bootstrap.php')) {
                $providers[$extension] = require $file;
            }
        }

        // @todo store $providers somewhere so that extensions can talk to each other
    }
}
