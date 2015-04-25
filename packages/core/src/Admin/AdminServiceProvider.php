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

        $assetManager = $this->app['flarum.admin.assetManager'];

        $assetManager->addFile([
            $root.'/js/admin/dist/app.js',
            $root.'/less/admin/app.less'
        ]);

        $this->publishes([
            $root.'/public/fonts' => public_path('flarum/fonts')
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
        $this->app['flarum.admin.assetManager'] = $this->app->share(function ($app) {
            return new AssetManager($app['files'], $app['path.public'].'/assets', 'admin');
        });
    }
}
