<?php namespace Flarum\BBCode;

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
        $events->subscribe('Flarum\BBCode\Listeners\AddClientAssets');
        $events->subscribe('Flarum\BBCode\Listeners\AddBBCodeFormatter');
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
