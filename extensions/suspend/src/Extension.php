<?php namespace Flarum\Suspend;

use Flarum\Support\Extension as BaseExtension;
use Illuminate\Events\Dispatcher;

class Extension extends BaseExtension
{
    public function listen(Dispatcher $events)
    {
        $events->subscribe('Flarum\Suspend\Listeners\AddClientAssets');
        $events->subscribe('Flarum\Suspend\Listeners\AddApiAttributes');
        $events->subscribe('Flarum\Suspend\Listeners\PersistData');
    }
}
