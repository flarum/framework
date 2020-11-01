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

    public function type(string $blueprint, string $serializer, array $channelsEnabledByDefault = [])
    {
        $this->blueprints[$blueprint] = $channelsEnabledByDefault;
        $this->serializers[$blueprint::getType()] = $serializer;

        return $this;
    }

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
