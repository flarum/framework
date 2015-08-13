<?php namespace Flarum\BBCode;

use Flarum\Support\Extension as BaseExtension;
use Illuminate\Events\Dispatcher;

class Extension extends BaseExtension
{
    public function listen(Dispatcher $events)
    {
        $events->subscribe('Flarum\BBCode\Listeners\AddClientAssets');
        $events->subscribe('Flarum\BBCode\Listeners\AddBBCodeFormatter');
    }
}
