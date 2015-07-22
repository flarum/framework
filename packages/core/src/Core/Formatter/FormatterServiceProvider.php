<?php namespace Flarum\Core\Formatter;

use Flarum\Support\ServiceProvider;
use Flarum\Extend;

class FormatterServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('flarum.formatter', 'Flarum\Core\Formatter\Formatter');
    }
}
