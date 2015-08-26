<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core;

use Flarum\Core;
use Flarum\Support\ServiceProvider;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Eloquent\Model;
use PDO;
use Flarum\Migrations\DatabaseMigrationRepository;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('flarum.db', function () {
            $factory = new ConnectionFactory($this->app);
            $connection = $factory->make($this->app->make('flarum.config')['database']);
            $connection->setEventDispatcher($this->app->make('Illuminate\Contracts\Events\Dispatcher'));
            $connection->setFetchMode(PDO::FETCH_CLASS);
            return $connection;
        });

        $this->app->alias('flarum.db', 'Illuminate\Database\ConnectionInterface');

        $this->app->singleton('Illuminate\Database\ConnectionResolverInterface', function () {
            $resolver = new ConnectionResolver([
                'flarum' => $this->app->make('flarum.db'),
            ]);
            $resolver->setDefaultConnection('flarum');
            return $resolver;
        });

        $this->app->alias('Illuminate\Database\ConnectionResolverInterface', 'db');

        if (Core::isInstalled()) {
            $this->app->booting(function () {
                $resolver = $this->app->make('Illuminate\Database\ConnectionResolverInterface');
                Model::setConnectionResolver($resolver);

                Model::setEventDispatcher($this->app->make('events'));
            });
        }


        $this->app->singleton('Flarum\Migrations\MigrationRepositoryInterface', function ($app) {
            return new DatabaseMigrationRepository($app['db'], 'migrations');
        });
    }
}
