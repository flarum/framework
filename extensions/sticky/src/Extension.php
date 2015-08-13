<?php namespace Flarum\Sticky;

use Flarum\Support\Extension as BaseExtension;
use Illuminate\Events\Dispatcher;

class Extension extends BaseExtension
{
    public function listen(Dispatcher $events)
    {
        $events->subscribe('Flarum\Sticky\Listeners\AddClientAssets');
        $events->subscribe('Flarum\Sticky\Listeners\AddApiAttributes');
        $events->subscribe('Flarum\Sticky\Listeners\PersistData');
        $events->subscribe('Flarum\Sticky\Listeners\PinStickiedDiscussionsToTop');
        $events->subscribe('Flarum\Sticky\Listeners\NotifyDiscussionStickied');
    }
}
