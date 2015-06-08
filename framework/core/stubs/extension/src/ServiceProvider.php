<?php namespace {{namespace}};

use Flarum\Support\ServiceProvider;
use Flarum\Extend\ForumAssets;

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
            ])
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
