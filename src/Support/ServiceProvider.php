<?php namespace Flarum\Support;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\Core\Models\Notification;
use Flarum\Core\Models\User;
use Flarum\Core\Models\Post;
use Flarum\Core\Models\Permission;
use Closure;

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
