<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Blueprint\DiscussionRenamedBlueprint;
use Illuminate\Contracts\Container\Container;

class NotificationServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('flarum.notification.drivers', function () {
            return [
                'alert' => Driver\AlertNotificationDriver::class,
                'email' => Driver\EmailNotificationDriver::class,
            ];
        });

        $this->container->singleton('flarum.notification.blueprints', function () {
            return [
                DiscussionRenamedBlueprint::class => ['alert']
            ];
        });
    }

    public function boot(Container $container): void
    {
        $this->setNotificationDrivers($container);
        $this->setNotificationTypes($container);
    }

    protected function setNotificationDrivers(Container $container): void
    {
        foreach ($container->make('flarum.notification.drivers') as $driverName => $driver) {
            NotificationSyncer::addNotificationDriver($driverName, $container->make($driver));
        }
    }

    protected function setNotificationTypes(Container $container): void
    {
        $blueprints = $container->make('flarum.notification.blueprints');

        foreach ($blueprints as $blueprint => $driversEnabledByDefault) {
            $this->addType($blueprint, $driversEnabledByDefault);
        }
    }

    /**
     * @param class-string<BlueprintInterface> $blueprintClass
     */
    protected function addType(string $blueprintClass, array $driversEnabledByDefault): void
    {
        Notification::setSubjectModel(
            $blueprintClass::getType(),
            $blueprintClass::getSubjectModel()
        );

        foreach (NotificationSyncer::getNotificationDrivers() as $driver) {
            $driver->registerType(
                $blueprintClass,
                $driversEnabledByDefault
            );
        }
    }
}
