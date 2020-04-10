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
    private $groupProcessors = [];

    /**
     * Add a display name driver.
     *
     * @param string $identifier Identifier for display name driver. E.g. 'username' for UserNameDriver
     * @param string $driver ::class attribute of driver class, which must implement Flarum\User\DisplayName\DriverInterface
     */
    public function displayNameDriver(string $identifier, $driver)
    {
        $this->displayNameDrivers[$identifier] = $driver;

        return $this;
    }


    /**
     * Add a callback to dynamically process a user's list of groups.
     * This can be used to add or remove groups to a user.
     *
     * The callable can be a closure or invokable class, and should accept:
     * - \Flarum\User\User $user: the user in question
     * - array $groupIds: an array of ids for the groups the user belongs to
     *
     * The callable should return:
     * - array $groupIds: an array of ids for the groups the user belongs to
     *
     * @param callable $callback
     */
    public function addGroupProcessor($callback)
    {
        $this->groupProcessors[] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('flarum.user.display_name.supported_drivers', function ($existingDrivers) {
            return array_merge($existingDrivers, $this->displayNameDrivers);
        });

        $container->extend('flarum.user.groupProcessors', function ($existingRelations) {
            return array_merge($existingRelations, $this->groupProcessors);
        });
    }
}
