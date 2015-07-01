<?php namespace Flarum\Locale;

use Flarum\Support\ServiceProvider;
use Flarum\Extend;

class LocaleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupLocale('en');
    }

    public function setupLocale($locale)
    {
        $dir = __DIR__.'/../../locale/'.$locale;

        $this->extend(
            (new Extend\Locale($locale))
                ->translations($dir.'/translations.yml')
                ->config($dir.'/config.php')
                ->js($dir.'/config.js')
        );
    }

    public function register()
    {
        $this->app->singleton('flarum.localeManager', 'Flarum\Locale\LocaleManager');
    }
}
