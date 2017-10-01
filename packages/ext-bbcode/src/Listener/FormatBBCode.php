<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\BBCode\Listener;

use Flarum\Formatter\Event\Configuring;
use Illuminate\Contracts\Events\Dispatcher;

class FormatBBCode
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(Configuring::class, [$this, 'addBBCodeFormatter']);
    }

    /**
     * @param Configuring $event
     */
    public function addBBCodeFormatter(Configuring $event)
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
        $event->configurator->BBCodes->addFromRepository('DEL');
        $event->configurator->BBCodes->addFromRepository('COLOR');
        $event->configurator->BBCodes->addFromRepository('CENTER');
        $event->configurator->BBCodes->addFromRepository('SIZE');
        $event->configurator->BBCodes->addFromRepository('*');
    }
}
