<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Queue;

use Flarum\Console\Event\Configuring;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\ErrorHandling\Registry;
use Flarum\Foundation\ErrorHandling\Reporter;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandling;
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
        Commands\WorkCommand::class,
    ];

    public function register()
    {
        // Register a simple connection factory that always returns the same
        // connection, as that is enough for our purposes.
        $this->app->singleton(Factory::class, function () {
            return new QueueFactory(function () {
                return $this->app->make('flarum.queue.connection');
            });
        });

        // Extensions can override this binding if they want to make Flarum use
        // a different queuing backend.
        $this->app->singleton('flarum.queue.connection', function ($app) {
            $queue = new SyncQueue;
            $queue->setContainer($app);

            return $queue;
        });

        $this->app->singleton(ExceptionHandling::class, function ($app) {
            return new ExceptionHandler($app['log']);
        });

        $this->app->singleton(Worker::class, function ($app) {
            return new Worker(
                new HackyManagerForWorker($app[Factory::class]),
                $app['events'],
                $app[ExceptionHandling::class]
            );
        });

        // Override the Laravel native Listener, so that we can ignore the environment
        // option and force the binary to flarum.
        $this->app->singleton(QueueListener::class, function ($app) {
            return new Listener($app->basePath());
        });

        // Bind a simple cache manager that returns the cache store.
        $this->app->singleton('cache', function ($app) {
            return new class($app) {
                public function __construct($app)
                {
                    $this->app = $app;
                }

                public function driver()
                {
                    return $this->app['cache.store'];
                }

                public function __call($name, $arguments)
                {
                    return call_user_func_array([$this->driver(), $name], $arguments);
                }
            };
        });

        $this->app->singleton('queue.failer', function () {
            return new NullFailedJobProvider();
        });

        $this->app->alias('flarum.queue.connection', Queue::class);

        $this->app->alias(ConnectorInterface::class, 'queue.connection');
        $this->app->alias(Factory::class, 'queue');
        $this->app->alias(Worker::class, 'queue.worker');
        $this->app->alias(Listener::class, 'queue.listener');

        $this->registerCommands();
    }

    protected function registerCommands()
    {
        $this->app['events']->listen(Configuring::class, function (Configuring $event) {
            $queue = $this->app->make(Queue::class);

            // There is no need to have the queue commands when using the sync driver.
            if ($queue instanceof SyncQueue) {
                return;
            }

            foreach ($this->commands as $command) {
                $event->addCommand($command);
            }
        });
    }

    public function boot()
    {
        $this->app['events']->listen(JobFailed::class, function (JobFailed $event) {
            /** @var Registry $registry */
            $registry = $this->app->make(Registry::class);

            $error = $registry->handle($event->exception);

            /** @var Reporter[] $reporters */
            $reporters = $this->app->tagged(Reporter::class);

            if ($error->shouldBeReported()) {
                foreach ($reporters as $reporter) {
                    $reporter->report($error->getException());
                }
            }
        });
    }
}
