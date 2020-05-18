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

class User implements ExtenderInterface
{
    private $displayNameDrivers = [];

    /**
     * Add a mail driver.
     *
     * @param string $identifier Identifier for display name driver. E.g. 'username' for UserNameDriver
     * @param string $driver ::class attribute of driver class, which must implement Flarum\User\DisplayName\DriverInterface
     */
    public function displayNameDriver(string $identifier, $driver)
    {
        $this->drivers[$identifier] = $driver;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('flarum.user.display_name.supported_drivers', function ($existingDrivers) {
            return array_merge($existingDrivers, $this->drivers);
        });
    }
}
