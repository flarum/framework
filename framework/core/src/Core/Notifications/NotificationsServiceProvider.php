<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Notifications;

use Flarum\Core\Users\User;
use Flarum\Events\RegisterNotificationTypes;
use Flarum\Support\ServiceProvider;
use Flarum\Extend;
use ReflectionClass;

class NotificationsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $events = $this->app->make('events');

        $events->subscribe('Flarum\Core\Notifications\Listeners\DiscussionRenamedNotifier');

        $this->registerNotificationTypes();
    }

    /**
     * Register notification types.
     *
     * @return void
     */
    public function registerNotificationTypes()
    {
        $blueprints = [
            'Flarum\Core\Notifications\DiscussionRenamedBlueprint' => ['alert']
        ];

        event(new RegisterNotificationTypes($blueprints));

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

            if ((new ReflectionClass($blueprint))->implementsInterface('Flarum\Core\Notifications\MailableBlueprint')) {
                User::addPreference(
                    User::getNotificationPreferenceKey($type, 'email'),
                    'boolval',
                    in_array('email', $enabled)
                );
            }
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
    }
}
