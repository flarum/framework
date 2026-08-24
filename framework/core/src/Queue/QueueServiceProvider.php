<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Queue;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Config;
use Flarum\Foundation\ErrorHandling\Registry;
use Flarum\Foundation\ErrorHandling\Reporter;
use Flarum\Foundation\Paths;
use Illuminate\Container\Container as ContainerImplementation;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandling;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Queue\Console as Commands;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Failed\NullFailedJobProvider;
use Illuminate\Queue\Listener as QueueListener;
use Illuminate\Queue\QueueRoutes;
use Illuminate\Queue\SyncQueue;
use Illuminate\Queue\Worker;

class QueueServiceProvider extends AbstractServiceProvider
{
    /**
     * Return the underlying driver connection, seeing through the RoutingQueue
     * wrapper. Code that needs the concrete driver type (e.g. to detect the
     * database or sync driver) must unwrap first, since the bound connection is
     * the wrapper.
     */
    protected static function unwrapQueue(mixed $queue): mixed
    {
        return $queue instanceof RoutingQueue ? $queue->getDriver() : $queue;
    }

    protected array $commands = [
        Commands\FlushFailedCommand::class,
        Commands\ForgetFailedCommand::class,
        Console\ListenCommand::class,
        Commands\ListFailedCommand::class,
        Console\PauseCommand::class,
        Console\ResumeCommand::class,
        Commands\RestartCommand::class,
        Commands\RetryCommand::class,
        Console\WorkCommand::class,
    ];

    public function register(): void
    {
        // Register a simple connection factory that always returns the same
        // connection, as that is enough for our purposes.
        // The queue stats provider backing the admin dashboard widget.
        // Extensions with a different queue backend (fof/redis, fof/horizon)
        // override this binding so the same core widget works for every driver.
        $this->container->singleton(QueueStatsProvider::class, function (Container $container) {
            return new DatabaseQueueStatsProvider($container->make('db.connection'));
        });

        $this->container->singleton(FailedJobs::class, function (Container $container) {
            return new FailedJobs(
                $container->make('queue.failer'),
                $container->make(Factory::class),
                $container->make('events')
            );
        });

        // The queue names known to this installation. Extensions that route
        // jobs onto named queues should append theirs so admin tooling can
        // offer per-queue controls.
        $this->container->singleton('flarum.queue.queues', function () {
            return ['default'];
        });

        // Laravel's native job-class => queue route manager. The queue
        // connection consults it on push (via RoutingQueue) to place a job onto
        // its registered queue without the dispatch site having to know.
        // Populated via Flarum\Extend\Queue::route().
        $this->container->singleton('queue.routes', function () {
            return new QueueRoutes();
        });

        $this->container->singleton(Factory::class, function (Container $container) {
            return new QueueFactory(
                function () use ($container) {
                    return $container->make('flarum.queue.connection');
                },
                $container->make('cache.store'),
                $container->make('events')
            );
        });

        // Extensions can override this binding if they want to make Flarum use
        // a different queuing backend.
        $this->container->singleton('flarum.queue.connection', function (ContainerImplementation $container) {
            /** @var Config $config */
            $config = $container->make(Config::class);
            $driver = $config->queueDriver() ?? 'sync';

            if ($driver === 'database') {
                $queue = new DatabaseQueue(
                    $container->make('db.connection'),
                    'queue_jobs',
                    'default',
                    60
                );
            } else {
                $queue = new SyncQueue;
            }

            $queue->setContainer($container);

            // Give the connection a name so events that carry it (e.g. WorkerIdle)
            // receive a string rather than null. illuminate/queue 13.15.0 type-hinted
            // WorkerIdle::$connectionName as `string`, so a null name now throws a
            // TypeError in the worker loop.
            $queue->setConnectionName('flarum');

            return $queue;
        });

        $this->container->singleton(ExceptionHandling::class, function (Container $container) {
            return new ExceptionHandler($container['log']);
        });

        $this->container->singleton(Worker::class, function (Container $container) {
            /** @var Config $config */
            $config = $container->make(Config::class);

            $worker = new Worker(
                $container[Factory::class],
                $container['events'],
                $container[ExceptionHandling::class],
                function () use ($config) {
                    return $config->inHighMaintenanceMode();
                }
            );

            $worker->setCache($container->make('cache.store'));

            return $worker;
        });

        // Override the Laravel native Listener, so that we can ignore the environment
        // option and force the binary to flarum.
        $this->container->singleton(QueueListener::class, function (Container $container) {
            return new Listener($container->make(Paths::class)->base);
        });

        // Bind a simple cache manager that returns the cache store.
        $this->container->singleton('cache', function (Container $container) {
            return new class($container) implements CacheFactory {
                public function __construct(
                    private readonly Container $container
                ) {
                }

                public function driver(): Repository
                {
                    return $this->container['cache.store'];
                }

                // We have to define this explicitly
                // so that we implement the interface.
                public function store($name = null): mixed
                {
                    return $this->__call($name, null);
                }

                public function __call(string $name, ?array $arguments): mixed
                {
                    return call_user_func_array([$this->driver(), $name], $arguments);
                }
            };
        });

        $this->container->singleton('queue.failer', function (Container $container) {
            $queue = static::unwrapQueue($container->make('flarum.queue.connection'));

            if ($queue instanceof DatabaseQueue) {
                /** @var Config $config */
                $config = $container->make(Config::class);

                return new DatabaseUuidFailedJobProvider(
                    $container->make('db'),
                    $config['database']['database'],
                    'queue_failed_jobs',
                    $container->make('db.connection')
                );
            }

            return new NullFailedJobProvider();
        });

        $this->container->alias('flarum.queue.connection', Queue::class);

        $this->container->alias(ConnectorInterface::class, 'queue.connection');
        $this->container->alias(Factory::class, 'queue');
        $this->container->alias(Factory::class, QueueFactory::class);
        $this->container->alias(Worker::class, 'queue.worker');
        $this->container->alias(Listener::class, 'queue.listener');

        $this->registerCommands();
        $this->registerSchedule();
    }

    protected function registerCommands(): void
    {
        $this->container->extend('flarum.console.commands', function ($commands, Container $container) {
            // Extensions can override the queue connection binding.
            // If they don't, it will be SyncQueue and we don't need queue commands.
            $connection = static::unwrapQueue($container->make('flarum.queue.connection'));

            if ($connection instanceof SyncQueue) {
                return $commands;
            }

            // Otherwise add our commands, while allowing them to be overridden by those
            // already added through the container.
            return array_merge($this->commands, $commands);
        });
    }

    protected function registerSchedule(): void
    {
        $this->container->extend('flarum.console.scheduled', function ($scheduled, Container $container) {
            $queue = static::unwrapQueue($container->make(Queue::class));

            // Only schedule the queue worker for the database driver specifically.
            // Other queue drivers (like Redis/Horizon) should handle their own scheduling.
            if ($queue instanceof DatabaseQueue) {
                $scheduled[] = [
                    'command' => Console\WorkCommand::class,
                    'args' => $container->make(Console\DatabaseWorkerArgs::class)->args(),
                    'callback' => function ($event) {
                        $event->everyMinute();
                    },
                ];
            }

            return $scheduled;
        });
    }

    public function boot(Dispatcher $events, Container $container): void
    {
        // Wrap the final queue connection so jobs are routed onto their
        // registered queue at push time. Done in boot(), after extensions have
        // set flarum.queue.connection (FoF Redis replaces it, Horizon wraps
        // it), so we decorate whatever they ended up with.
        $container->extend('flarum.queue.connection', function ($queue, $container) {
            // The sync driver runs jobs inline the moment they are pushed, so a
            // routed queue name has no effect — leave it unwrapped so the
            // `instanceof SyncQueue` checks used to detect "no real queue" keep
            // working. Only real (async) drivers are wrapped for routing.
            if ($queue instanceof RoutingQueue || $queue instanceof SyncQueue) {
                return $queue;
            }

            // Core names its own driver, but an extension that replaces the
            // connection (e.g. fof/redis) may leave it unnamed. A null name
            // flows into pause/resume bookkeeping and the WorkerIdle event,
            // both of which expect a string — so give any unnamed driver the
            // default connection name before wrapping it.
            if ($queue->getConnectionName() === null) {
                $queue->setConnectionName('flarum');
            }

            return new RoutingQueue($queue, $container->make('queue.routes'));
        });

        $events->listen(JobFailed::class, function (JobFailed $event) use ($container) {
            /** @var Registry $registry */
            $registry = $container->make(Registry::class);

            $error = $registry->handle($event->exception);

            /** @var Reporter[] $reporters */
            $reporters = $container->tagged(Reporter::class);

            if ($error->shouldBeReported()) {
                foreach ($reporters as $reporter) {
                    $reporter->report($error->getException());
                }
            }
        });

        $events->subscribe(QueueRestarter::class);
    }
}
