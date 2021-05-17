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
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandling;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Queue\Console as Commands;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Failed\NullFailedJobProvider;
use Illuminate\Queue\Listener as QueueListener;
use Illuminate\Queue\SyncQueue;
use Illuminate\Queue\Worker;

class QueueServiceProvider extends AbstractServiceProvider
{
    protected $commands = [
        Commands\FlushFailedCommand::class,
        Commands\ForgetFailedCommand::class,
        Console\ListenCommand::class,
        Commands\ListFailedCommand::class,
        Commands\RestartCommand::class,
        Commands\RetryCommand::class,
        Console\WorkCommand::class,
    ];

    public function register()
    {
        // Register a simple connection factory that always returns the same
        // connection, as that is enough for our purposes.
        $this->container->singleton(Factory::class, function (Container $container) {
            return new QueueFactory(function () use ($container) {
                return $container->make('flarum.queue.connection');
            });
        });

        // Extensions can override this binding if they want to make Flarum use
        // a different queuing backend.
        $this->container->singleton('flarum.queue.connection', function (Container $container) {
            $queue = new SyncQueue;
            $queue->setContainer($container);

            return $queue;
        });

        $this->container->singleton(ExceptionHandling::class, function (Container $container) {
            return new ExceptionHandler($container['log']);
        });

        $this->container->singleton(Worker::class, function (Container $container) {
            /** @var Config $config */
            $config = $container->make(Config::class);

            return new Worker(
                $container[Factory::class],
                $container['events'],
                $container[ExceptionHandling::class],
                function () use ($config) {
                    return $config->inMaintenanceMode();
                }
            );
        });

        // Override the Laravel native Listener, so that we can ignore the environment
        // option and force the binary to flarum.
        $this->container->singleton(QueueListener::class, function (Container $container) {
            return new Listener($container->make(Paths::class)->base);
        });

        // Bind a simple cache manager that returns the cache store.
        $this->container->singleton('cache', function (Container $container) {
            return new class($container) implements CacheFactory {
                public function __construct($container)
                {
                    $this->container = $container;
                }

                public function driver()
                {
                    return $this->container['cache.store'];
                }

                // We have to define this explicitly
                // so that we implement the interface.
                public function store($name = null)
                {
                    return $this->__call($name, null);
                }

                public function __call($name, $arguments)
                {
                    return call_user_func_array([$this->driver(), $name], $arguments);
                }
            };
        });

        $this->container->singleton('queue.failer', function () {
            return new NullFailedJobProvider();
        });

        $this->container->alias('flarum.queue.connection', Queue::class);

        $this->container->alias(ConnectorInterface::class, 'queue.connection');
        $this->container->alias(Factory::class, 'queue');
        $this->container->alias(Worker::class, 'queue.worker');
        $this->container->alias(Listener::class, 'queue.listener');

        $this->registerCommands();
    }

    protected function registerCommands()
    {
        $this->container->extend('flarum.console.commands', function ($commands, Container $container) {
            $queue = $container->make(Queue::class);

            // There is no need to have the queue commands when using the sync driver.
            if ($queue instanceof SyncQueue) {
                return $commands;
            }

            // Otherwise add our commands, while allowing them to be overridden by those
            // already added through the container.
            return array_merge($this->commands, $commands);
        });
    }

    public function boot(Dispatcher $events, Container $container)
    {
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
    }
}
