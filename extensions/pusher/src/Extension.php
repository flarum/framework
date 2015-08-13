<?php namespace Flarum\Pusher;

use Flarum\Support\Extension as BaseExtension;
use Illuminate\Events\Dispatcher;

class Extension extends BaseExtension
{
    public function listen(Dispatcher $events)
    {
        $events->subscribe('Flarum\Pusher\Listeners\AddClientAssets');
        $events->subscribe('Flarum\Pusher\Listeners\PushNewPosts');
        $events->subscribe('Flarum\Pusher\Listeners\AddApiAttributes');
    }
}
