<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Foundation\Paths;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;

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

// The following are all deprecated perpetually.
// They are needed by some laravel components we use (e.g. task scheduling)
// They should NOT be used in extension code.

if (! function_exists('app')) {
    /**
     * @deprecated perpetually.
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

if (! function_exists('base_path')) {
    /**
     * @deprecated perpetually.
     *
     * Get the path to the base of the install.
     *
     * @param  string  $path
     * @return string
     */
    function base_path($path = '')
    {
        return resolve(Paths::class)->base.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('public_path')) {
    /**
     * @deprecated perpetually.
     *
     * Get the path to the public folder.
     *
     * @param  string  $path
     * @return string
     */
    function public_path($path = '')
    {
        return resolve(Paths::class)->public.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('storage_path')) {
    /**
     * @deprecated perpetually.
     *
     * Get the path to the storage folder.
     *
     * @param  string  $path
     * @return string
     */
    function storage_path($path = '')
    {
        return resolve(Paths::class)->storage.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('event')) {
    /**
     * @deprecated perpetually.
     *
     * Fire an event and call the listeners.
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array|null
     */
    function event($event, $payload = [], $halt = false)
    {
        return resolve('events')->dispatch($event, $payload, $halt);
    }
}

if (! function_exists('config')) {
    /**
     * @deprecated do not use, will be transferred to flarum/laravel-helpers.
     */
    function config(string $key, $default = null)
    {
        return resolve(Repository::class)->get($key, $default);
    }
}
