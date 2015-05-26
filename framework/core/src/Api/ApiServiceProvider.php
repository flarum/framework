<?php namespace Flarum\Api;

use Flarum\Http\Router;
use Illuminate\Support\ServiceProvider;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton(
            'Illuminate\Contracts\Debug\ExceptionHandler',
            'Flarum\Api\ExceptionHandler'
        );

        $this->app->singleton('Flarum\Http\Router', function() { return new Router(); });

        include __DIR__.'/routes.php';
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Flarum\Support\Actor');
    }
}
