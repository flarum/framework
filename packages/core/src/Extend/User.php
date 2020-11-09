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
     * Dynamically process a user's list of groups when calculating permissions.
     * This can be used to give a user permissions for groups they aren't actually in, based on context.
     * It will not change the group badges displayed for the user.
     *
     * @param callable|string $callback
     *
     * The callable can be a closure or invokable class, and should accept:
     * - \Flarum\User\User $user: the user in question.
     * - array $groupIds: an array of ids for the groups the user belongs to.
     *
     * The callable should return:
     * - array $groupIds: an array of ids for the groups the user belongs to.
     */
    public function permissionGroups($callback)
    {
        $this->groupProcessors[] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('flarum.user.display_name.supported_drivers', function ($existingDrivers) {
            return array_merge($existingDrivers, $this->displayNameDrivers);
        });

        $container->extend('flarum.user.group_processors', function ($existingRelations) {
            return array_merge($existingRelations, $this->groupProcessors);
        });
    }
}
