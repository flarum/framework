<?php namespace Flarum\Support;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    public function extend()
    {
        foreach (func_get_args() as $extender) {
            $extender->extend($this->app);
        }
    }
}
