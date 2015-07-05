<?php namespace Flarum\Core;

use Flarum\Core\Users\User;
use Flarum\Support\ServiceProvider;
use Flarum\Extend;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../../views', 'flarum');

        $this->app->make('Illuminate\Contracts\Bus\Dispatcher')->mapUsing(function ($command) {
            return get_class($command).'Handler@handle';
        });

        Forum::allow('*', function (Forum $forum, User $user, $action) {
            return $user->hasPermission('forum.'.$action) ?: null;
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('flarum.forum', 'Flarum\Core\Forum');

        // TODO: probably use Illuminate's AggregateServiceProvider
        // functionality, because it includes the 'provides' stuff.
        $this->app->register('Flarum\Core\Activity\ActivityServiceProvider');
        $this->app->register('Flarum\Core\Discussions\DiscussionsServiceProvider');
        $this->app->register('Flarum\Core\Formatter\FormatterServiceProvider');
        $this->app->register('Flarum\Core\Notifications\NotificationsServiceProvider');
        $this->app->register('Flarum\Core\Posts\PostsServiceProvider');
        $this->app->register('Flarum\Core\Users\UsersServiceProvider');
    }
}
