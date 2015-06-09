<?php namespace Flarum\Admin;

use Illuminate\Support\ServiceProvider;
use Flarum\Support\AssetManager;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $root = __DIR__.'/../..';

        $this->loadViewsFrom($root.'/views', 'flarum.admin');

        $this->publishes([
            $root.'/public/fonts' => public_path('assets/fonts')
        ]);

        include __DIR__.'/routes.php';
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('flarum.admin.assetManager', function () {
            return new AssetManager($this->app->make('files'), public_path('assets'), 'admin');
        });
    }
}
