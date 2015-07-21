<?php namespace Flarum\Locale;

use Flarum\Events\RegisterLocales;
use Flarum\Support\ServiceProvider;

class LocaleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $manager = $this->app->make('flarum.localeManager');

        $this->registerLocale($manager, 'en');

        event(new RegisterLocales($manager));
    }

    public function registerLocale(LocaleManager $manager, $locale)
    {
        $dir = __DIR__.'/../../locale/'.$locale;

        $manager->addTranslations($locale, $dir.'/translations.yml');
        $manager->addConfig($locale, $dir.'/config.php');
        $manager->addJsFile($locale, $dir.'/config.js');
    }

    public function register()
    {
        $this->app->singleton('Flarum\Locale\LocaleManager');

        $this->app->alias('Flarum\Locale\LocaleManager', 'flarum.localeManager');
    }
}
