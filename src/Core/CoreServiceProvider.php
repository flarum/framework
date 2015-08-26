<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core;

use Flarum\Core\Users\User;
use Flarum\Events\ModelAllow;
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

        $events = $this->app->make('events');

        $events->listen(ModelAllow::class, function (ModelAllow $event) {
            if ($event->model instanceof Forum &&
                $event->actor->hasPermission('forum.'.$event->action)) {
                return true;
            }
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

        // FIXME: probably use Illuminate's AggregateServiceProvider
        // functionality, because it includes the 'provides' stuff.
        $this->app->register('Flarum\Core\Discussions\DiscussionsServiceProvider');
        $this->app->register('Flarum\Core\Formatter\FormatterServiceProvider');
        $this->app->register('Flarum\Core\Groups\GroupsServiceProvider');
        $this->app->register('Flarum\Core\Notifications\NotificationsServiceProvider');
        $this->app->register('Flarum\Core\Posts\PostsServiceProvider');
        $this->app->register('Flarum\Core\Users\UsersServiceProvider');
    }
}
