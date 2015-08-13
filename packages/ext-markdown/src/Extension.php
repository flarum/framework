<?php namespace Flarum\Markdown;

use Flarum\Support\Extension as BaseExtension;
use Illuminate\Events\Dispatcher;

class Extension extends BaseExtension
{
    public function listen(Dispatcher $events)
    {
        $events->subscribe('Flarum\Markdown\Listeners\AddClientAssets');
        $events->subscribe('Flarum\Markdown\Listeners\AddMarkdownFormatter');
    }
}
