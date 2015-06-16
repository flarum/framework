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
        // @todo don't support func_get_args
        foreach (func_get_args() as $extenders) {
            if (! is_array($extenders)) {
                $extenders = [$extenders];
            }
            foreach ($extenders as $extender) {
                $extender->extend($this->app);
            }
        }
    }
}
