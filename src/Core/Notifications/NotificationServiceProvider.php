<?php namespace Flarum\Core\Notifications;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Flarum\Core\Models\User;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(Dispatcher $events)
    {
        $notifier = app('Flarum\Core\Notifications\Notifier');

        $notifier->registerMethod('alert', 'Flarum\Core\Notifications\Senders\NotificationAlerter');
        $notifier->registerMethod('email', 'Flarum\Core\Notifications\Senders\NotificationEmailer');

        $notifier->registerType('Flarum\Core\Notifications\Types\DiscussionRenamedNotification', ['alert' => true]);

        $events->subscribe('Flarum\Core\Handlers\Events\DiscussionRenamedNotifier');
    }

    public function register()
    {
        $this->app->bind(
            'Flarum\Core\Repositories\NotificationRepositoryInterface',
            'Flarum\Core\Repositories\EloquentNotificationRepository'
        );

        $this->app->singleton('Flarum\Core\Notifications\Notifier');
    }
}
