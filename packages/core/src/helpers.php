<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Foundation\Paths;
use Illuminate\Container\Container;

if (! function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @param  string  $make
     * @param  array   $parameters
     * @return mixed|\Illuminate\Foundation\Application
     */
    function app($make = null, $parameters = [])
    {
        if (is_null($make)) {
            return Container::getInstance();
        }

        return Container::getInstance()->make($make, $parameters);
    }
}

if (! function_exists('base_path')) {
    /**
     * Get the path to the base of the install.
     *
     * @param  string  $path
     * @return string
     * @deprecated Will be removed in Beta.15.
     */
    function base_path($path = '')
    {
        return app(Paths::class)->base.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('public_path')) {
    /**
     * Get the path to the public folder.
     *
     * @param  string  $path
     * @return string
     * @deprecated Will be removed in Beta.15.
     */
    function public_path($path = '')
    {
        return app(Paths::class)->public.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('storage_path')) {
    /**
     * Get the path to the storage folder.
     *
     * @param  string  $path
     * @return string
     * @deprecated Will be removed in Beta.15.
     */
    function storage_path($path = '')
    {
        return app(Paths::class)->storage.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('event')) {
    /**
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
