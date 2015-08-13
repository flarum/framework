<?php namespace Flarum\Lock;

use Flarum\Support\Extension as BaseExtension;
use Illuminate\Events\Dispatcher;

class Extension extends BaseExtension
{
    public function listen(Dispatcher $events)
    {
        $events->subscribe('Flarum\Lock\Listeners\AddClientAssets');
        $events->subscribe('Flarum\Lock\Listeners\AddApiAttributes');
        $events->subscribe('Flarum\Lock\Listeners\PersistData');
        $events->subscribe('Flarum\Lock\Listeners\NotifyDiscussionLocked');
        $events->subscribe('Flarum\Lock\Listeners\ConfigurePermissions');
    }
}
