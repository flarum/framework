<?php namespace Flarum\Web;

use Illuminate\Support\ServiceProvider;

class WebServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../../views', 'flarum.web');

        // Shouldn't do all this asset stuff in boot, because then it gets called on API requests
        $assetManager = $this->app['flarum.web.assetManager'];

        $assetManager->add([
            __DIR__.'/../../ember/forum/dist/assets/flarum.css',
            __DIR__.'/../../ember/forum/dist/assets/vendor.js',
            __DIR__.'/../../ember/forum/dist/assets/flarum.js'
        ]);

        include __DIR__.'/routes.php';

        $this->publishes([
            __DIR__.'/../../ember/forum/dist/font-awesome' => public_path('font-awesome')
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['flarum.web.assetManager'] = $this->app->share(function($app)
        {
            return new AssetManager($app['files'], $app['path.public']);
        });
    }
}
