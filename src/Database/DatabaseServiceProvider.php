<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
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
        $this->app->singleton(Manager::class, function ($app) {
            $manager = new Manager($app);

            $config = $app->config('database');
            $config['engine'] = 'InnoDB';
            $config['prefix_indexes'] = true;

            $manager->addConnection($config, 'flarum');

            return $manager;
        });

        $this->app->singleton(ConnectionResolverInterface::class, function ($app) {
            $manager = $app->make(Manager::class);
            $manager->setAsGlobal();
            $manager->bootEloquent();

            $dbManager = $manager->getDatabaseManager();
            $dbManager->setDefaultConnection('flarum');

            return $dbManager;
        });

        $this->app->alias(ConnectionResolverInterface::class, 'db');

        $this->app->singleton(ConnectionInterface::class, function ($app) {
            $resolver = $app->make(ConnectionResolverInterface::class);

            return $resolver->connection();
        });

        $this->app->alias(ConnectionInterface::class, 'db.connection');
        $this->app->alias(ConnectionInterface::class, 'flarum.db');

        $this->app->singleton('flarum.model.customRelations', function () {
            return [];
        });

        $this->app->singleton('flarum.model.dateCallbacks', function () {
            return [];
        });

        $this->app->singleton('flarum.model.defaultAttributeCallbacks', function () {
            return [];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        AbstractModel::setConnectionResolver($this->app->make(ConnectionResolverInterface::class));
        AbstractModel::setEventDispatcher($this->app->make('events'));

        foreach ($this->app->make('flarum.model.customRelations') as $modelClass => $callbacks) {
            foreach ($callbacks as  $name => $relation) {
                if (is_string($relation)) {
                    $relation = $this->app->make($relation);
                }

                AbstractModel::addCustomRelation($modelClass, $name, $relation);
            }
        }

        foreach ($this->app->make('flarum.model.dateCallbacks') as $modelClass => $callbacks) {
            foreach ($callbacks as $callback) {
                if (is_string($callback)) {
                    $callback = $this->app->make($callback);
                }

                AbstractModel::addDateCallback($modelClass, $callback);
            }
        }

        foreach ($this->app->make('flarum.model.defaultAttributeCallbacks') as $modelClass => $callbacks) {
            foreach ($callbacks as $callback) {
                if (is_string($callback)) {
                    $callback = $this->app->make($callback);
                }

                AbstractModel::addDefaultAttributeCallback($modelClass, $callback);
            }
        }
    }
}
