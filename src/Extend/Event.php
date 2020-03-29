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
     * Add a listener to a domain event dispatched by flarum or an extension to flarum
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
            if (is_string($listener[1])) {
                $listener[1] = $container->make($listener[1]);
            }

            $events->listen($listener[0], $listener[1]);
        }
    }
}
