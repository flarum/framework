<?php namespace Flarum\Emoji;

use Flarum\Support\Extension as BaseExtension;
use Illuminate\Events\Dispatcher;

class Extension extends BaseExtension
{
    public function listen(Dispatcher $events)
    {
        $events->subscribe('Flarum\Emoji\Listeners\AddClientAssets');
        $events->subscribe('Flarum\Emoji\Listeners\AddEmoticons');
    }
}
