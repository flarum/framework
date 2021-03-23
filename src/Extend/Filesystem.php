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

class Filesystem implements ExtenderInterface
{
    private $disks = [];
    private $drivers = [];

    /**
     * Declare a new filesystem disk.
     * Disks represent storage locations, and are backed by storage drivers.
     * Flarum core uses disks for storing assets and avatars.
     *
     * By default, the "local" driver will be used for disks.
     * The "local" driver represents the filesystem where your Flarum installation is running.
     *
     * To declare a new disk, you must provide default configuration a "local" driver.
     *
     * @param string $disk: The name of the disk
     * @param string|callable: A callback with parameters:
     *                           - \Flarum\Foundation\Paths $paths
     *                           - \Flarum\Http\UrlGenerator $url
     *                         which returns a Laravel disk config array.
     *                         The `driver` key is not necessary for this array, and will be ignored.
     *
     * @example
     * ```
     * ->disk('flarum-uploads', function (Paths $paths, UrlGenerator $url) {
     *       return [
     *          'root'   => "$paths->public/assets/uploads",
     *          'url'    => $url->to('forum')->path('assets/uploads')
     *       ];
     *   });
     * ```
     *
     * @see https://laravel.com/docs/8.x/filesystem#configuration
     */
    public function disk(string $name, $callback)
    {
        $this->disks[$name] = $callback;

        return $this;
    }

    /**
     * Register a new filesystem driver.
     * Drivers must implement `\Flarum\Filesystem\DriverInterface`.
     *
     * @param string $name: The name of the driver
     * @param string $driverClass: The ::class attribute of the driver.
     */
    public function driver(string $name, string $driverClass)
    {
        $this->drivers[$name] = $driverClass;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('flarum.filesystem.disks', function ($existingDisks) {
            return array_merge($existingDisks, $this->disks);
        });

        $container->extend('flarum.filesystem.drivers', function ($existingDrivers) {
            return array_merge($existingDrivers, $this->drivers);
        });
    }
}
