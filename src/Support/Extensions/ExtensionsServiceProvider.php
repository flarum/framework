<?php namespace Flarum\Support\Extensions;

use Flarum\Core;
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
        // Extensions will not be registered if Flarum is not installed yet
        if (!Core::isInstalled()) {
            return;
        }

        $extensions = json_decode(Core::config('extensions_enabled'), true);
        $providers = [];

        foreach ($extensions as $extension) {
            if (file_exists($file = public_path().'/extensions/'.$extension.'/bootstrap.php') ||
                file_exists($file = base_path().'/extensions/'.$extension.'/bootstrap.php')) {
                $providers[$extension] = require $file;
            }
        }

        // @todo store $providers somewhere (in Core?) so that extensions can talk to each other
    }
}
