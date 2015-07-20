<?php namespace Flarum\Sticky;

use Flarum\Support\Extension as BaseExtension;
use Illuminate\Contracts\Events\Dispatcher;

class Extension extends BaseExtension
{
    public function boot(Dispatcher $events)
    {
        $events->subscribe('Flarum\Sticky\Listeners\AddClientAssets');
        $events->subscribe('Flarum\Sticky\Listeners\AddApiAttributes');
        $events->subscribe('Flarum\Sticky\Listeners\PersistData');
        $events->subscribe('Flarum\Sticky\Listeners\PinStickiedDiscussionsToTop');
        $events->subscribe('Flarum\Sticky\Listeners\NotifyDiscussionStickied');
    }
}
