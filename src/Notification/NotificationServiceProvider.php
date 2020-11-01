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

    public function setNotificationDrivers()
    {
        foreach ($this->app->make('flarum.notification.drivers') as $driverName => $driver) {
            Notification::addNotificationDriver($driverName, $this->app->make($driver));
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

        foreach ($blueprints as $blueprint => $channelsEnabledByDefault) {
            $this->addType($blueprint, $channelsEnabledByDefault);
        }
    }

    protected function addType(string $blueprint, array $channelsEnabledByDefault)
    {
        Notification::setSubjectModel(
            $type = $blueprint::getType(),
            $blueprint::getSubjectModel()
        );

        foreach (Notification::getNotificationDrivers() as $driverName => $driver) {
            $driver->addUserPreference(
                $blueprint,
                in_array($driverName, $channelsEnabledByDefault)
            );
        }
    }
}
