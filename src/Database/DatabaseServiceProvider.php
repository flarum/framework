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
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;

class DatabaseServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton(ConnectionResolverInterface::class, function () {
            $manager = new Manager($this->app);

            $dbConfig = $this->app->config('database');
            $dbConfig['engine'] = 'InnoDB';
            $dbConfig['prefix_indexes'] = true;

            $manager->addConnection($dbConfig, 'flarum');
            $manager->getDatabaseManager()->setDefaultConnection('flarum');

            $manager->bootEloquent();

            return $manager->getDatabaseManager();
        });

        $this->app->alias(ConnectionResolverInterface::class, 'db');

        $this->app->singleton(ConnectionInterface::class, function ($app) {
            $resolver = $app->make(ConnectionResolverInterface::class);

            return $resolver->connection();
        });

        $this->app->alias(ConnectionInterface::class, 'db.connection');
        $this->app->alias(ConnectionInterface::class, 'flarum.db');
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
