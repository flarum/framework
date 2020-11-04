<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification;

use Flarum\Event\ConfigureNotificationTypes;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Notification\Blueprint\DiscussionRenamedBlueprint;

class NotificationServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton('flarum.notification.drivers', function () {
            return [
                'alert' => Driver\AlertNotificationDriver::class,
                'email' => Driver\EmailNotificationDriver::class,
            ];
        });

        $this->app->singleton('flarum.notification.blueprints', function () {
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
        foreach ($this->app->make('flarum.notification.drivers') as $driverName => $driver) {
            NotificationSyncer::addNotificationDriver($driverName, $this->app->make($driver));
        }
    }

    /**
     * Register notification types.
     */
    protected function setNotificationTypes()
    {
        $blueprints = $this->app->make('flarum.notification.blueprints');

        // Deprecated in beta 15, remove in beta 16
        $this->app->make('events')->dispatch(
            new ConfigureNotificationTypes($blueprints)
        );

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
