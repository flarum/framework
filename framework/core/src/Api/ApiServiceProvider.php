<?php namespace Flarum\Api;

use Illuminate\Support\ServiceProvider;
use Flarum\Api\Serializers\BaseSerializer;

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
