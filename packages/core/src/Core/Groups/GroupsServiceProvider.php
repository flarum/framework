<?php namespace Flarum\Core\Groups;

use Flarum\Events\ModelAllow;
use Flarum\Support\ServiceProvider;
use Illuminate\Contracts\Container\Container;

class GroupsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        Group::setValidator($this->app->make('validator'));

        $events = $this->app->make('events');

        $events->listen(ModelAllow::class, function (ModelAllow $event) {
            if ($event->model instanceof Group) {
                if ($event->actor->hasPermission('group.'.$event->action)) {
                    return true;
                }
            }
        });
    }
}
