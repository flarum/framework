<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database;

use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Paths;
use Illuminate\Container\Container as ContainerImplementation;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;

class DatabaseServiceProvider extends AbstractServiceProvider
{
    protected static array $fakers = [];

    public function register(): void
    {
        $this->registerEloquentFactory();
        $this->registerBuilderMacros();

        $this->container->singleton(Manager::class, function (ContainerImplementation $container) {
            $manager = new Manager($container);

            $config = $container['flarum']->config('database');

            if (in_array($config['driver'], ['mysql', 'mariadb'])) {
                $config['engine'] = 'InnoDB';
            } elseif ($config['driver'] === 'sqlite' && ! file_exists($config['database'])) {
                $config['database'] = $container->make(Paths::class)->base.'/'.$config['database'];
            }

            $config['prefix_indexes'] = true;

            $manager->addConnection($config, 'flarum');

            return $manager;
        });

        $this->container->singleton(ConnectionResolverInterface::class, function (Container $container) {
            $manager = $container->make(Manager::class);
            $manager->setAsGlobal();
            $manager->bootEloquent();

            $dbManager = $manager->getDatabaseManager();
            $dbManager->setDefaultConnection('flarum');

            return $dbManager;
        });

        $this->container->alias(ConnectionResolverInterface::class, 'db');

        $this->container->singleton(ConnectionInterface::class, function (Container $container) {
            $resolver = $container->make(ConnectionResolverInterface::class);

            return $resolver->connection();
        });

        $this->container->alias(ConnectionInterface::class, 'db.connection');
        $this->container->alias(ConnectionInterface::class, 'flarum.db');

        $this->container->singleton(MigrationRepositoryInterface::class, function (Container $container) {
            return new DatabaseMigrationRepository($container['flarum.db'], 'migrations');
        });

        $this->container->singleton('flarum.database.model_private_checkers', function () {
            return [];
        });
    }

    protected function registerBuilderMacros(): void
    {
        $drivers = [
            'mysql' => 'MySql',
            'mariadb' => 'MariaDb',
            'pgsql' => 'PgSql',
            'sqlite' => 'Sqlite',
        ];

        foreach ([QueryBuilder::class, EloquentBuilder::class] as $builder) {
            foreach ($drivers as $driver => $macro) {
                $builder::macro('when'.$macro, function ($callback, $else) use ($driver) {
                    /** @var QueryBuilder|EloquentBuilder $this */
                    if ($this->getConnection()->getDriverName() === $driver) {
                        $callback($this);
                    } else {
                        $else($this);
                    }

                    return $this;
                });

                $builder::macro('unless'.$macro, function ($callback, $else) use ($driver) {
                    /** @var QueryBuilder|EloquentBuilder $this */
                    if ($this->getConnection()->getDriverName() !== $driver) {
                        $callback($this);
                    } else {
                        $else($this);
                    }

                    return $this;
                });
            }
        }
    }

    protected function registerEloquentFactory(): void
    {
        $this->app->singleton(FakerGenerator::class, function ($app, $parameters) {
            $locale = $parameters['locale'] ?? 'en_US';

            if (! isset(static::$fakers[$locale])) {
                static::$fakers[$locale] = FakerFactory::create($locale);
            }

            static::$fakers[$locale]->unique(true);

            return static::$fakers[$locale];
        });
    }

    public function boot(Container $container): void
    {
        AbstractModel::setConnectionResolver($container->make(ConnectionResolverInterface::class));
        AbstractModel::setEventDispatcher($container->make('events'));

        Factory::guessFactoryNamesUsing(function (string $modelName) {
            return $modelName.'Factory';
        });
        Factory::guessModelNamesUsing(function (Factory $factory) {
            return Str::replaceLast('Factory', '', $factory::class);
        });

        foreach ($container->make('flarum.database.model_private_checkers') as $modelClass => $checkers) {
            $modelClass::saving(function ($instance) use ($checkers) {
                foreach ($checkers as $checker) {
                    if ($checker($instance) === true) {
                        $instance->is_private = true;

                        return;
                    }
                }

                $instance->is_private = false;
            });
        }
    }
}
