<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Queue;

use Illuminate\Bus\Dispatcher;
use Illuminate\Contracts\Bus\Dispatcher as DispatcherContract;
use Illuminate\Encryption\Encrypter;
use Illuminate\Encryption\McryptEncrypter;
use Illuminate\Queue\QueueServiceProvider as Provider;

class QueueServiceProvider extends Provider
{
    /**
     * {@inheritdoc}
     */
    protected function registerManager()
    {
        $this->registerEncrypter();
        $this->registerQueue();
        $this->registerDispatcher();
    }

    protected function registerEncrypter()
    {
        $this->app->singleton('encrypter', function ($app) {
            $key = md5($app->url());

            $cipher = 'AES-256-CBC';

            if (Encrypter::supported($key, $cipher)) {
                return new Encrypter($key, $cipher);
            } elseif (McryptEncrypter::supported($key, $cipher)) {
                return new McryptEncrypter($key, $cipher);
            } else {
                throw new RuntimeException('No supported encrypter found. The cipher and / or key length are invalid.');
            }
        });
    }

    protected function registerQueue()
    {
        $this->app->singleton('queue', function ($app) {
            // Once we have an instance of the queue manager, we will register the various
            // resolvers for the queue connectors. These connectors are responsible for
            // creating the classes that accept queue configs and instantiate queues.
            $manager = new QueueManager($app);

            // We've disabled all other queue connectors for now. Once we're able to
            // configure any of the other connectors, we can easily reset the old
            // behavior of the parent provider by registering all connectors.
            $this->registerSyncConnector($manager);

            return $manager;
        });

        $this->app->singleton('queue.connection', function ($app) {
            return $app['queue']->connection();
        });
    }

    protected function registerDispatcher()
    {
        $this->app->singleton(DispatcherContract::class, function ($app) {
            return new Dispatcher($app, function () use ($app) {
                return $app['queue']->connection();
            });
        });
    }
}
