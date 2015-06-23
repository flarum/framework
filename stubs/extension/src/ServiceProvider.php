<?php namespace {{namespace}};

use Flarum\Support\ServiceProvider;
use Flarum\Extend;

class {{classPrefix}}ServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->extend([
            (new Extend\Locale('en'))->translations(__DIR__.'/../locale/en.yml'),

            (new Extend\ForumClient())
                ->assets([
                    __DIR__.'/../js/dist/extension.js',
                    __DIR__.'/../less/extension.less'
                ])
                ->translations([
                    // Add the keys of translations you would like to be available
                    // for use by the JS client application.
                ])
        ]);
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
