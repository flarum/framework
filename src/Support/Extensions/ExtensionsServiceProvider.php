<?php namespace Flarum\Support\Extensions;

use Flarum\Core;
use Illuminate\Support\ServiceProvider;

class ExtensionsServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        // Extensions will not be registered if Flarum is not installed yet
        if (!Core::isInstalled()) {
            return;
        }

        $config = $this->app->make('Flarum\Core\Settings\SettingsRepository')->get('extensions_enabled');
        $extensions = json_decode($config, true);
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
