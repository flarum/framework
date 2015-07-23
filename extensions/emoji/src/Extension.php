<?php namespace Flarum\Emoji;

use Flarum\Support\Extension as BaseExtension;
use Illuminate\Events\Dispatcher;

class Extension extends BaseExtension
{
    public function boot(Dispatcher $events)
    {
        $events->subscribe('Flarum\Emoji\Listeners\AddClientAssets');
        $events->subscribe('Flarum\Emoji\Listeners\AddEmoticons');
    }
}
