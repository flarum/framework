<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Groups;

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
