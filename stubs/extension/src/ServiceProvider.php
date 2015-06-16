<?php namespace {{namespace}};

use Flarum\Support\ServiceProvider;
use Flarum\Extend\ForumAssets;
use Flarum\Extend\Locale;
use Flarum\Extend\ForumTranslations;

class {{classPrefix}}ServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->extend(
            new ForumAssets([
                __DIR__.'/../js/dist/extension.js',
                __DIR__.'/../less/extension.less'
            ]),

            (new Locale('en'))->translations(__DIR__.'/../locale/en.yml'),

            new ForumTranslations([
                // Add the keys of translations you would like to be available
                // for use by the JS client application.
            ]),
        );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
