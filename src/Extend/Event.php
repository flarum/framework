<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;
use Illuminate\Events\Dispatcher;

class Event implements ExtenderInterface
{
    private $listeners = [];

    /**
     * Add a listener to a domain event dispatched by flarum or a flarum extension.
     *
     * The listener can either be:
     *  - a callback function
     *  - a The class attribute of class with a public `handle` method, which accepts an instance of the event as a parameter
     *  - An array where the first element is the class attribute of the listener class, and the second element is
     *    a string with the name of the method that will handle the event. This method should accept an instance of the event as a parameter.
     *
     * @param string $event
     * @param callable $listener
     */
    public function listen(string $event, $listener)
    {
        $this->listeners[] = [$event, $listener];

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $events = $container->make(Dispatcher::class);

        foreach ($this->listeners as $listener) {
            $events->listen($listener[0], $listener[1]);
        }
    }
}
