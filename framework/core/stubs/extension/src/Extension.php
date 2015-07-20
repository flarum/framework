<?php namespace {{namespace}};

use Flarum\Support\Extension;
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
        $events->subscribe('{{namespace}}\Listeners\AddClientAssets');
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
