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
            (new Extend\EventSubscriber('Flarum\Core\Handlers\Events\DiscussionRenamedNotifier')),

            (new Extend\NotificationType('Flarum\Core\Notifications\DiscussionRenamedNotification'))
                ->subjectSerializer('Flarum\Api\Serializers\DiscussionBasicSerializer')
                ->enableByDefault('alert')
        ]);
    }

    public function register()
    {
        $this->app->bind(
            'Flarum\Core\Repositories\NotificationRepositoryInterface',
            'Flarum\Core\Repositories\EloquentNotificationRepository'
        );
    }
}
