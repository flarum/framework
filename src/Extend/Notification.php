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

class Notification implements ExtenderInterface
{
    private $blueprints = [];
    private $serializers = [];
    private $drivers = [];

    /**
     * @param string $blueprint The ::class attribute of the blueprint class.
     *                          This blueprint should implement \Flarum\Notification\Blueprint\BlueprintInterface.
     * @param string $serializer The ::class attribute of the serializer class.
     *                           This serializer should extend from \Flarum\Api\Serializer\AbstractSerializer.
     * @param array $driversEnabledByDefault The names of the drivers enabled by default for this notification type.
     * @return self
     */
    public function type(string $blueprint, string $serializer, array $driversEnabledByDefault = [])
    {
        $this->blueprints[$blueprint] = $driversEnabledByDefault;
        $this->serializers[$blueprint::getType()] = $serializer;

        return $this;
    }

    /**
     * @param string $driverName The name of the notification driver.
     * @param string $driver The ::class attribute of the driver class.
     *                       This driver should implement \Flarum\Notification\Driver\NotificationDriverInterface.
     * @return self
     */
    public function driver(string $driverName, string $driver)
    {
        $this->drivers[$driverName] = $driver;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('flarum.notification.blueprints', function ($existingBlueprints) {
            return array_merge($existingBlueprints, $this->blueprints);
        });

        $container->extend('flarum.api.notification_serializers', function ($existingSerializers) {
            return array_merge($existingSerializers, $this->serializers);
        });

        $container->extend('flarum.notification.drivers', function ($existingDrivers) {
            return array_merge($existingDrivers, $this->drivers);
        });
    }
}
