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
use Flarum\Post\CommentPost;
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
        $this->container->singleton(Manager::class, function ($container) {
            $manager = new Manager($container);

            $config = $this->container['flarum']->config('database');
            $config['engine'] = 'InnoDB';
            $config['prefix_indexes'] = true;

            $manager->addConnection($config, 'flarum');

            return $manager;
        });

        $this->container->singleton(ConnectionResolverInterface::class, function ($container) {
            $manager = $container->make(Manager::class);
            $manager->setAsGlobal();
            $manager->bootEloquent();

            $dbManager = $manager->getDatabaseManager();
            $dbManager->setDefaultConnection('flarum');

            return $dbManager;
        });

        $this->container->alias(ConnectionResolverInterface::class, 'db');

        $this->container->singleton(ConnectionInterface::class, function ($container) {
            $resolver = $container->make(ConnectionResolverInterface::class);

            return $resolver->connection();
        });

        $this->container->alias(ConnectionInterface::class, 'db.connection');
        $this->container->alias(ConnectionInterface::class, 'flarum.db');

        $this->container->singleton(MigrationRepositoryInterface::class, function ($container) {
            return new DatabaseMigrationRepository($container['flarum.db'], 'migrations');
        });

        $this->container->singleton('flarum.database.model_private_checkers', function () {
            // Discussion and CommentPost are explicitly listed here to trigger the deprecated
            // event-based model privacy system. They should be removed in beta 17.
            return [
                Discussion::class => [],
                CommentPost::class => []
            ];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        AbstractModel::setConnectionResolver($this->container->make(ConnectionResolverInterface::class));
        AbstractModel::setEventDispatcher($this->container->make('events'));

        foreach ($this->container->make('flarum.database.model_private_checkers') as $modelClass => $checkers) {
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

                $instance->is_private = $this->container->make('events')->until($event) === true;
            });
        }
    }
}
