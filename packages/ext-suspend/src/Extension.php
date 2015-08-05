<?php namespace Flarum\Suspend;

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
        $events->subscribe('Flarum\Suspend\Listeners\AddClientAssets');
        $events->subscribe('Flarum\Suspend\Listeners\AddApiAttributes');
        $events->subscribe('Flarum\Suspend\Listeners\PersistData');
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
