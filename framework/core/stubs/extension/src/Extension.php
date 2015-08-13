<?php namespace {{namespace}};

use Flarum\Support\Extension as BaseExtension;
use Illuminate\Events\Dispatcher;

class Extension extends BaseExtension
{
    public function listen(Dispatcher $events)
    {
        $events->subscribe('{{namespace}}\Listeners\AddClientAssets');
    }
}
