<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Database;

use Flarum\Foundation\AbstractServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Connectors\ConnectionFactory;

class DatabaseServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton('flarum.db', function () {
            $factory = new ConnectionFactory($this->app);

            $dbConfig = $this->app->config('database');
            $dbConfig['engine'] = 'InnoDB';
            $connection = $factory->make($dbConfig);
            $connection->setEventDispatcher($this->app->make(Dispatcher::class));

            return $connection;
        });

        $this->app->alias('flarum.db', ConnectionInterface::class);

        $this->app->singleton(ConnectionResolverInterface::class, function () {
            $resolver = new ConnectionResolver([
                'flarum' => $this->app->make('flarum.db'),
            ]);
            $resolver->setDefaultConnection('flarum');

            return $resolver;
        });

        $this->app->alias(ConnectionResolverInterface::class, 'db');
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        AbstractModel::setConnectionResolver($this->app->make(ConnectionResolverInterface::class));
        AbstractModel::setEventDispatcher($this->app->make('events'));
    }
}
