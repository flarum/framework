<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Container\Container;

if (! function_exists('resolve')) {
    /**
     * Resolve a service from the container.
     *
     * @param  string  $name
     * @param  array  $parameters
     * @return mixed
     */
    function resolve($name, $parameters = [])
    {
        return Container::getInstance()->make($name, $parameters);
    }
}

if (! function_exists('app')) {
    /**
     * @deprecated beta 16, remove beta 17. Use container() instead.
     * Get the available container instance.
     *
     * @param  string  $make
     * @param  array   $parameters
     * @return mixed|\Illuminate\Container\Container
     */
    function app($make = null, $parameters = [])
    {
        if (is_null($make)) {
            return Container::getInstance();
        }

        return resolve($make, $parameters);
    }
}

if (! function_exists('event')) {
    /**
     * @deprecated beta 16, removed in beta 17
     * Fire an event and call the listeners.
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array|null
     */
    function event($event, $payload = [], $halt = false)
    {
        return app('events')->dispatch($event, $payload, $halt);
    }
}
