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
    public function boot()
    {
        $this->registerNotificationTypes();
    }

    /**
     * Register notification types.
     */
    public function registerNotificationTypes()
    {
        $blueprints = [
            DiscussionRenamedBlueprint::class => ['alert']
        ];

        $this->app->make('events')->dispatch(
            new ConfigureNotificationTypes($blueprints)
        );

        foreach ($blueprints as $blueprint => $enabled) {
            Notification::setSubjectModel(
                $type = $blueprint::getType(),
                $blueprint::getSubjectModel()
            );

            User::addPreference(
                User::getNotificationPreferenceKey($type, 'alert'),
                'boolval',
                in_array('alert', $enabled)
            );

            if ((new ReflectionClass($blueprint))->implementsInterface(MailableInterface::class)) {
                User::addPreference(
                    User::getNotificationPreferenceKey($type, 'email'),
                    'boolval',
                    in_array('email', $enabled)
                );
            }
        }
    }
}
