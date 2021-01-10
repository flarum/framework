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
use Illuminate\Contracts\Events\Dispatcher;

class Event implements ExtenderInterface
{
    private $listeners = [];
    private $subscribers = [];

    /**
     * Add a listener to a domain event dispatched by flarum or a flarum extension.
     *
     * The listener can either be:
     *  - a callback function or
     *  - the class attribute of a class with a public `handle` method, which accepts an instance of the event as a parameter
     *
     * @param string $event
     * @param callable|string $listener
     */
    public function listen(string $event, $listener)
    {
        $this->listeners[] = [$event, $listener];

        return $this;
    }

    /**
     * Add a subscriber for a set of domain events dispatched by flarum or a flarum extension.
     *
     * @param string $subscriber
     */
    public function subscribe(string $subscriber)
    {
        $this->subscribers[] = $subscriber;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $events = $container->make(Dispatcher::class);

        foreach ($this->listeners as $listener) {
            $events->listen($listener[0], $listener[1]);
        }

        $app = $container->make('flarum');

        $app->booted(function () use ($events) {
            foreach ($this->subscribers as $subscriber) {
                $events->subscribe($subscriber);
            }
        });
    }
}
