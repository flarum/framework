<?php namespace Flarum\BBCode\Listeners;

use Flarum\Events\FormatterConfigurator;
use Illuminate\Contracts\Events\Dispatcher;

class AddBBCodeFormatter
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(FormatterConfigurator::class, [$this, 'addBBCodeFormatter']);
    }

    public function addBBCodeFormatter(FormatterConfigurator $event)
    {
        $event->configurator->BBCodes->addFromRepository('B');
        $event->configurator->BBCodes->addFromRepository('I');
        $event->configurator->BBCodes->addFromRepository('U');
        $event->configurator->BBCodes->addFromRepository('S');
        $event->configurator->BBCodes->addFromRepository('URL');
        $event->configurator->BBCodes->addFromRepository('IMG');
        $event->configurator->BBCodes->addFromRepository('EMAIL');
        $event->configurator->BBCodes->addFromRepository('CODE');
        $event->configurator->BBCodes->addFromRepository('QUOTE');
        $event->configurator->BBCodes->addFromRepository('LIST');
        $event->configurator->BBCodes->addFromRepository('*');
    }
}
