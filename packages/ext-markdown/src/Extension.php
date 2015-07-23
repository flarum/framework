<?php namespace Flarum\Markdown;

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
        $events->subscribe('Flarum\Markdown\Listeners\AddClientAssets');
        $events->subscribe('Flarum\Markdown\Listeners\AddMarkdownFormatter');
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
