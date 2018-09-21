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

use Flarum\Database\DatabaseMigrationRepository;
use Flarum\Database\Migrator;
use Flarum\Foundation\Application;
use Flarum\Foundation\InstalledSite;
use Flarum\Foundation\SiteInterface;
use Flarum\Foundation\UninstalledSite;
use Flarum\Http\Server;
use Flarum\Install\Console\DataProviderInterface;
use Flarum\Install\Console\DefaultsDataProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Connectors\ConnectionFactory;

trait CreatesForum
{
    /**
     * @var Server
     */
    protected $http;

    /**
     * @var SiteInterface
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
        if ($this->isInstalled) {
            $this->site = new InstalledSite(
                __DIR__.'/../../tmp',
                __DIR__.'/../../tmp/public',
                $this->getFlarumConfig()
            );
        } else {
            $this->site = new UninstalledSite(
                __DIR__.'/../../tmp',
                __DIR__.'/../../tmp/public'
            );
        }
    }

    protected function createsHttpForum()
    {
        $this->app = $this->site->bootApp();
    }

    protected function collectsConfiguration()
    {
        $this->configuration = new DefaultsDataProvider();

        $this->configuration->setDebugMode();
        $this->configuration->setSetting('mail_driver', 'log');

        $database = $this->configuration->getDatabaseConfiguration();
        $database['host'] = env('DB_HOST', $database['host']);
        $database['database'] = env('DB_DATABASE', $database['database']);
        $database['username'] = env('DB_USERNAME', $database['username']);
        $database['password'] = env('DB_PASSWORD', $database['password']);
        $database['prefix'] = env('DB_PREFIX', $database['prefix']);
        $this->configuration->setDatabaseConfiguration($database);
    }

    protected function refreshApplication()
    {
        $this->collectsConfiguration();

        $this->seedsDatabase();

        $this->createsSite();

        $this->createsHttpForum();
    }

    protected function teardownApplication()
    {
        /** @var ConnectionInterface $connection */
        $connection = app(ConnectionInterface::class);
        $connection->rollBack();
    }

    protected function getFlarumConfig()
    {
        $dbConfig = $this->configuration->getDatabaseConfiguration();

        return [
            'debug'    => $this->configuration->isDebugMode(),
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
            'url'      => $this->configuration->getBaseUrl(),
            'paths'    => [
                'api'   => 'api',
                'admin' => 'admin',
            ],
        ];
    }

    protected function seedsDatabase()
    {
        if (! $this->isInstalled) {
            return;
        }

        $app = app(\Illuminate\Contracts\Foundation\Application::class);

        $factory = new ConnectionFactory($app);
        $db = $factory->make($this->configuration->getDatabaseConfiguration());

        $repository = new DatabaseMigrationRepository($db, 'migrations');
        $migrator = new Migrator($repository, $db, app('files'));

        if (! $migrator->getRepository()->repositoryExists()) {
            $migrator->getRepository()->createRepository();
        }

        $migrator->run(__DIR__.'/../../../migrations');

        $db->beginTransaction();
    }
}
