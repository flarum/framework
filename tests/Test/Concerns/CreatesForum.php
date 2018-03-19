<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\Test\Concerns;

use Flarum\Database\Migrator;
use Flarum\Foundation\Application;
use Flarum\Foundation\Site;
use Flarum\Http\Server;
use Flarum\Install\Console\DataProviderInterface;
use Flarum\Install\Console\DefaultsDataProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Schema\Builder;

trait CreatesForum
{
    /**
     * @var Server
     */
    protected $http;

    /**
     * @var Site
     */
    protected $site;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var DataProviderInterface
     */
    protected $configuration;

    /**
     * Make the test set up Flarum as an installed app.
     *
     * @var bool
     */
    protected $isInstalled = true;

    protected function createsSite()
    {
        $this->site = (new Site)
            ->setBasePath(__DIR__.'/../../tmp')
            ->setPublicPath(__DIR__.'/../../tmp/public');
    }

    protected function createsHttpForum()
    {
        $this->http = Server::fromSite(
            $this->site
        );

        $this->app = $this->http->app;
    }

    protected function refreshApplication()
    {
        $this->createsSite();

        $data = new DefaultsDataProvider();

        $data->setDebugMode();
        $data->setSetting('mail_driver', 'log');

        $database = $data->getDatabaseConfiguration();
        $database['database'] = env('DB_DATABASE', 'flarum');
        $database['username'] = env('DB_USERNAME', 'root');
        $database['password'] = env('DB_PASSWORD', '');
        $data->setDatabaseConfiguration($database);

        $this->configuration = $data;

        $this->setsApplicationConfiguration($data);

        $this->seedsApplication();

        $this->createsHttpForum();
    }

    protected function teardownApplication()
    {
        /** @var ConnectionInterface $connection */
        $connection = $this->app->make(ConnectionInterface::class);
        $connection->rollBack();
    }

    protected function setsApplicationConfiguration(DataProviderInterface $data)
    {
        if ($this->isInstalled) {
            $dbConfig = $data->getDatabaseConfiguration();
            $this->site->setConfig(
                $config = [
                    'debug'    => $data->isDebugMode(),
                    'database' => [
                        'driver'    => $dbConfig['driver'],
                        'host'      => $dbConfig['host'],
                        'database'  => $dbConfig['database'],
                        'username'  => $dbConfig['username'],
                        'password'  => $dbConfig['password'],
                        'charset'   => 'utf8mb4',
                        'collation' => 'utf8mb4_unicode_ci',
                        'prefix'    => $dbConfig['prefix'],
                        'port'      => $dbConfig['port'],
                        'strict'    => false
                    ],
                    'url'      => $data->getBaseUrl(),
                    'paths'    => [
                        'api'   => 'api',
                        'admin' => 'admin',
                    ],
                ]
            );
        }
    }

    protected function seedsApplication()
    {
        if ($this->isInstalled) {
            $app = app(\Illuminate\Contracts\Foundation\Application::class);

            $app->bind(Builder::class, function ($container) {
                return $container->make(ConnectionInterface::class)->getSchemaBuilder();
            });

            /** @var Migrator $migrator */
            $migrator = $app->make(Migrator::class);
            if (! $migrator->getRepository()->repositoryExists()) {
                $migrator->getRepository()->createRepository();
            }

            $migrator->run(__DIR__.'/../../../migrations');

            /** @var ConnectionInterface $connection */
            $connection = $app->make(\Illuminate\Database\ConnectionInterface::class);
            $connection->beginTransaction();
        }
    }
}
