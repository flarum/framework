<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Queue;

use Flarum\Console\Event\Configuring;
use Flarum\Foundation\AbstractServiceProvider;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandling;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Queue\Console as Commands;
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
//        Commands\RestartCommand::class,
        Commands\RetryCommand::class,
        Commands\WorkCommand::class,
    ];

    public function register()
    {
        if (! defined('ARTISAN_BINARY')) {
            define('ARTISAN_BINARY', 'flarum');
        }

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

        $this->app->singleton(QueueListener::class, function ($app) {
            return new Listener($app->basePath());
        });

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
            };
        });

        $this->app->singleton('queue.failer', function () {
            return new NullFailedJobProvider();
        });

        $this->app->alias(ConnectorInterface::class, 'queue.connection');
        $this->app->alias(Factory::class, 'queue');
        $this->app->alias(Worker::class, 'queue.worker');
        $this->app->alias(Listener::class, 'queue.listener');

        $this->registerCommands();
    }

    protected function registerCommands()
    {
        $this->app['events']->listen(Configuring::class, function (Configuring $event) {
            foreach ($this->commands as $command) {
                $event->addCommand($command);
            }
        });
    }
}
