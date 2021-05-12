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
     * @param string $event: Name of the event, can be the ::class attribute of the event class.
     * @param callable|string $listener
     *
     * The listener can either be:
     *  - A callback function that accepts an instance of the event as a parameter.
     *  - The ::class attribute of a class with a public `handle` method, which accepts an instance of the event as a parameter.
     *  - An array, where the first argument is an object or class name, and the second argument is the method on the
     *    first argument that should be executed as the listener.
     *
     * @return self
     */
    public function listen(string $event, $listener): self
    {
        $this->listeners[] = [$event, $listener];

        return $this;
    }

    /**
     * Add a subscriber for a set of domain events dispatched by flarum or a flarum extension.
     * Event subscribers are classes that may subscribe to multiple events from within the subscriber class itself,
     * allowing you to define several event handlers within a single class.
     *
     * @see https://laravel.com/docs/8.x/events#writing-event-subscribers
     *
     * @param string $subscriber: The ::class attribute of the subscriber class.
     * @return self
     */
    public function subscribe(string $subscriber): self
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
