<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Extension\Extension;
use Flarum\Foundation\ContainerUtil;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Driver\NotificationDriverInterface;
use Flarum\Notification\NotificationSyncer;
use Illuminate\Contracts\Container\Container;

class Notification implements ExtenderInterface
{
    private array $blueprints = [];
    private array $serializers = [];
    private array $drivers = [];
    private array $typesEnabledByDefault = [];
    private array $beforeSendingCallbacks = [];

    /**
     * @param class-string<BlueprintInterface> $blueprint: The ::class attribute of the blueprint class.
     *                          This blueprint should implement \Flarum\Notification\Blueprint\BlueprintInterface.
     * @param class-string<AbstractSerializer> $serializer: The ::class attribute of the serializer class.
     *                           This serializer should extend from \Flarum\Api\Serializer\AbstractSerializer.
     * @param string[] $driversEnabledByDefault: The names of the drivers enabled by default for this notification type.
     *                                       (example: alert, email).
     * @return self
     */
    public function type(string $blueprint, string $serializer, array $driversEnabledByDefault = []): self
    {
        $this->blueprints[$blueprint] = $driversEnabledByDefault;
        $this->serializers[$blueprint::getType()] = $serializer;

        return $this;
    }

    /**
     * @param string $driverName: The name of the notification driver.
     * @param class-string<NotificationDriverInterface> $driver: The ::class attribute of the driver class.
     *                       This driver should implement \Flarum\Notification\Driver\NotificationDriverInterface.
     * @param string[] $typesEnabledByDefault: The names of blueprint classes of types enabled by default for this driver.
     * @return self
     */
    public function driver(string $driverName, string $driver, array $typesEnabledByDefault = []): self
    {
        $this->drivers[$driverName] = $driver;
        $this->typesEnabledByDefault[$driverName] = $typesEnabledByDefault;

        return $this;
    }

    /**
     * @param (callable(BlueprintInterface $blueprint, \Flarum\User\User[] $newRecipients): \Flarum\User\User[])|class-string $callback
     *
     * The callback can be a closure or an invokable class, and should accept:
     * - \Flarum\Notification\Blueprint\BlueprintInterface $blueprint
     * - \Flarum\User\User[] $newRecipients
     *
     * The callable should return an array of recipients.
     * - \Flarum\User\User[] $newRecipients
     *
     * @return self
     */
    public function beforeSending(callable|string $callback): self
    {
        $this->beforeSendingCallbacks[] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null): void
    {
        $container->extend('flarum.notification.blueprints', function ($existingBlueprints) {
            $existingBlueprints = array_merge($existingBlueprints, $this->blueprints);

            foreach ($this->typesEnabledByDefault as $driverName => $typesEnabledByDefault) {
                foreach ($typesEnabledByDefault as $blueprintClass) {
                    if (isset($existingBlueprints[$blueprintClass]) && (! in_array($driverName, $existingBlueprints[$blueprintClass]))) {
                        $existingBlueprints[$blueprintClass][] = $driverName;
                    }
                }
            }

            return $existingBlueprints;
        });

        $container->extend('flarum.api.notification_serializers', function ($existingSerializers) {
            return array_merge($existingSerializers, $this->serializers);
        });

        $container->extend('flarum.notification.drivers', function ($existingDrivers) {
            return array_merge($existingDrivers, $this->drivers);
        });

        foreach ($this->beforeSendingCallbacks as $callback) {
            NotificationSyncer::beforeSending(ContainerUtil::wrapCallback($callback, $container));
        }
    }
}
