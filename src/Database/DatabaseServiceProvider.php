<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database;

use Flarum\Discussion\Discussion;
use Flarum\Event\GetModelIsPrivate;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Post\Post;
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

            $config = $this->app['flarum']->config('database');
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

        $this->app->singleton(MigrationRepositoryInterface::class, function ($app) {
            return new DatabaseMigrationRepository($app['flarum.db'], 'migrations');
        });

        $this->app->singleton('flarum.database.model_private_checkers', function () {
            // Discussion and Post are explicitly listed here to trigger the deprecated
            // event-based model privacy system. They should be removed in beta 17.
            return [
                Discussion::class => [],
                Post::class => []
            ];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        AbstractModel::setConnectionResolver($this->app->make(ConnectionResolverInterface::class));
        AbstractModel::setEventDispatcher($this->app->make('events'));

        foreach ($this->app->make('flarum.database.model_private_checkers') as $modelClass => $checkers) {
            $modelClass::saving(function ($instance) use ($checkers) {
                foreach ($checkers as $checker) {
                    if ($checker($instance) === true) {
                        $instance->is_private = true;

                        return;
                    }
                }

                $instance->is_private = false;

                // @deprecated BC layer, remove beta 17
                $event = new GetModelIsPrivate($instance);

                $instance->is_private = $this->app->make('events')->until($event) === true;
            });
        }
    }
}
