<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Notification;

use Flarum\Event\ConfigureNotificationTypes;
use Flarum\Foundation\AbstractServiceProvider;
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
            'Flarum\Notification\Notification\DiscussionRenamedBlueprint' => ['alert']
        ];

        $this->app->make('events')->fire(
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

            if ((new ReflectionClass($blueprint))->implementsInterface('Flarum\Notification\Notification\MailableInterface')) {
                User::addPreference(
                    User::getNotificationPreferenceKey($type, 'email'),
                    'boolval',
                    in_array('email', $enabled)
                );
            }
        }
    }
}
