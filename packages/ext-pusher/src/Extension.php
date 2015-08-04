<?php namespace Flarum\Pusher;

use Flarum\Support\Extension as BaseExtension;
use Illuminate\Events\Dispatcher;

class Extension extends BaseExtension
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(Dispatcher $events)
    {
        $events->subscribe('Flarum\Pusher\Listeners\AddClientAssets');
        $events->subscribe('Flarum\Pusher\Listeners\PushNewPosts');
        $events->subscribe('Flarum\Pusher\Listeners\AddApiAttributes');
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
