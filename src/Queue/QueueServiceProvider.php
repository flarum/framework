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
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandling;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Queue\Connectors\SyncConnector;
use Illuminate\Queue\Console as Commands;
use Illuminate\Queue\Failed\NullFailedJobProvider;
use Illuminate\Queue\Listener;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\Worker;

class QueueServiceProvider extends AbstractServiceProvider
{
    protected $commands = [
        Commands\FailedTableCommand::class,
        Commands\FlushFailedCommand::class,
        Commands\ForgetFailedCommand::class,
        Commands\ListenCommand::class,
        Commands\ListFailedCommand::class,
        Commands\RestartCommand::class,
        Commands\RetryCommand::class,
        Commands\TableCommand::class,
        Commands\WorkCommand::class,
    ];

    public function register()
    {
        $this->app->singleton(ConnectorInterface::class, function ($app) {
            return $app['queue']->connection();
        });

        $this->app->singleton(Factory::class, function ($app) {
            $manager = new QueueManager($app);

            $manager->addConnector('sync', function () {
                return new SyncConnector;
            });

            return $manager;
        });

        $this->app->singleton(ExceptionHandling::class, function ($app) {
            return new ExceptionHandler($app['log']);
        });

        $this->app->singleton(Worker::class, function ($app) {
            return new Worker(
                $app['queue'], $app['events'], $app->make(ExceptionHandling::class)
            );
        });

        $this->app->singleton(Listener::class, function ($app) {
            return new Listener($app->basePath());
        });

        $this->app->singleton('cache', function ($app) {
            $manager = new CacheManager($app);

            $manager->extend('flarum', function () use ($app) {
                return $app['cache.store'];
            });

            $manager->setDefaultDriver('flarum');

            return $manager;
        });

        $this->app->singleton('queue.failer', function () {
            return new NullFailedJobProvider();
        });

        $this->app['config']->set('cache.stores.flarum', ['driver' => 'flarum']);

        $this->app->alias(ConnectorInterface::class, 'queue.connection');
        $this->app->alias(Factory::class, 'queue');
        $this->app->alias(Worker::class, 'queue.worker');
        $this->app->alias(Listener::class, 'queue.listener');

        $this->registerCommands();
    }

    protected function registerCommands()
    {
        $this->app['events']->listen(Configuring::class, function (Configuring $event) {
            if (! in_array($event->app['config']->get('queue.default'), ['sync', 'null'])) {
                foreach ($this->commands as $command) {
                    $event->addCommand($command);
                }
            }
        });
    }
}
