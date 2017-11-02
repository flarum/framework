<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Bus;

use Flarum\Foundation\AbstractServiceProvider;
use Illuminate\Contracts\Bus\Dispatcher as BusContract;
use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;

class BusServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->app->bind(BusContract::class, function ($app) {
            return new Dispatcher($app, function ($connection = null) use ($app) {
                return $app[QueueFactoryContract::class]->connection($connection);
            });
        });
    }
}
