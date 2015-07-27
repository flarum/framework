<?php namespace Flarum\Markdown\Listeners;

use Flarum\Events\FormatterConfigurator;
use Illuminate\Contracts\Events\Dispatcher;

class AddMarkdownFormatter
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(FormatterConfigurator::class, [$this, 'addMarkdownFormatter']);
    }

    public function addMarkdownFormatter(FormatterConfigurator $event)
    {
        $event->configurator->Litedown;
    }
}
