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
use Flarum\User\User;
use ReflectionClass;

class NotificationServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
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
        $this->registerNotificationTypes();
    }

    /**
     * Register notification types.
     */
    protected function registerNotificationTypes()
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

        User::addPreference(
            User::getNotificationPreferenceKey($type, 'alert'),
            'boolval',
            in_array('alert', $channelsEnabledByDefault)
        );

        if ((new ReflectionClass($blueprint))->implementsInterface(MailableInterface::class)) {
            User::addPreference(
                User::getNotificationPreferenceKey($type, 'email'),
                'boolval',
                in_array('email', $channelsEnabledByDefault)
            );
        }
    }
}
