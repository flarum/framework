<?php namespace Flarum\Likes;

use Flarum\Support\Extension as BaseExtension;
use Illuminate\Events\Dispatcher;

class Extension extends BaseExtension
{
    public function listen(Dispatcher $events)
    {
        $events->subscribe('Flarum\Likes\Listeners\AddClientAssets');
        $events->subscribe('Flarum\Likes\Listeners\AddModelRelationship');
        $events->subscribe('Flarum\Likes\Listeners\AddApiAttributes');
        $events->subscribe('Flarum\Likes\Listeners\PersistData');
        $events->subscribe('Flarum\Likes\Listeners\NotifyPostLiked');
    }
}
