<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Bus;

use Flarum\Foundation\AbstractServiceProvider;
use Illuminate\Bus\Dispatcher as BaseDispatcher;
use Illuminate\Contracts\Bus\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Bus\QueueingDispatcher as QueueingDispatcherContract;
use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;

class BusServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->app->bind(BaseDispatcher::class, function ($app) {
            return new Dispatcher($app, function ($connection = null) use ($app) {
                return $app[QueueFactoryContract::class]->connection($connection);
            });
        });

        $this->app->alias(
            BaseDispatcher::class,
            DispatcherContract::class
        );

        $this->app->alias(
            BaseDispatcher::class,
            QueueingDispatcherContract::class
        );
    }
}
