<?php namespace Flarum\Core\Notifications;

use Flarum\Support\ServiceProvider;
use Flarum\Core\Models\User;
use Flarum\Core\Notifications\Notifier;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\Extend\NotificationType;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(Dispatcher $events, Notifier $notifier)
    {
        $events->subscribe('Flarum\Core\Handlers\Events\DiscussionRenamedNotifier');

        $notifier->registerMethod('alert', 'Flarum\Core\Notifications\Senders\NotificationAlerter');
        $notifier->registerMethod('email', 'Flarum\Core\Notifications\Senders\NotificationEmailer');

        $this->extend(
            (new NotificationType('Flarum\Core\Notifications\Types\DiscussionRenamedNotification'))->enableByDefault('alert')
        );
    }

    public function register()
    {
        $this->app->bind(
            'Flarum\Core\Repositories\NotificationRepositoryInterface',
            'Flarum\Core\Repositories\EloquentNotificationRepository'
        );

        $this->app->singleton('Flarum\Core\Notifications\Notifier');
        $this->app->alias('Flarum\Core\Notifications\Notifier', 'flarum.notifier');
    }
}
