<?php namespace Flarum\Core\Notifications;

use Flarum\Support\ServiceProvider;
use Flarum\Extend;

class NotificationsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->extend([
            (new Extend\EventSubscriber('Flarum\Core\Notifications\Listeners\DiscussionRenamedNotifier')),

            (new Extend\NotificationType('Flarum\Core\Notifications\DiscussionRenamedBlueprint'))
                ->subjectSerializer('Flarum\Api\Serializers\DiscussionBasicSerializer')
                ->enableByDefault('alert')
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            'Flarum\Core\Notifications\NotificationRepositoryInterface',
            'Flarum\Core\Notifications\EloquentNotificationRepository'
        );
    }
}
