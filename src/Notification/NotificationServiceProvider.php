<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Notification\Blueprint\DiscussionRenamedBlueprint;

class NotificationServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
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

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->setNotificationDrivers();
        $this->setNotificationTypes();
    }

    /**
     * Register notification drivers.
     */
    protected function setNotificationDrivers()
    {
        foreach ($this->container->make('flarum.notification.drivers') as $driverName => $driver) {
            NotificationSyncer::addNotificationDriver($driverName, $this->container->make($driver));
        }
    }

    /**
     * Register notification types.
     */
    protected function setNotificationTypes()
    {
        $blueprints = $this->container->make('flarum.notification.blueprints');

        foreach ($blueprints as $blueprint => $driversEnabledByDefault) {
            $this->addType($blueprint, $driversEnabledByDefault);
        }
    }

    protected function addType(string $blueprint, array $driversEnabledByDefault)
    {
        Notification::setSubjectModel(
            $type = $blueprint::getType(),
            $blueprint::getSubjectModel()
        );

        foreach (NotificationSyncer::getNotificationDrivers() as $driverName => $driver) {
            $driver->registerType(
                $blueprint,
                $driversEnabledByDefault
            );
        }
    }
}
