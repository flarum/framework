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
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Database\MySqlConnection;
use Illuminate\Filesystem\Filesystem;

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
        $paths = [
            'base' => __DIR__.'/../../tmp',
            'public' => __DIR__.'/../../tmp/public',
            'storage' => __DIR__.'/../../tmp/storage',
        ];

        if ($this->isInstalled) {
            $this->site = new InstalledSite($paths, $this->getFlarumConfig());
        } else {
            $this->site = new UninstalledSite($paths);
        }
    }

    protected function createsHttpForum()
    {
        $this->app = $this->site->bootApp();
    }

    protected function getDatabaseConfiguration()
    {
        return [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST', 'localhost'),
            'database'  => env('DB_DATABASE', 'flarum'),
            'username'  => env('DB_USERNAME', 'root'),
            'password'  => env('DB_PASSWORD', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => env('DB_PREFIX', ''),
            'port'      => '3306',
            'strict'    => false,
        ];
    }

    protected function refreshApplication()
    {
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
        return [
            'debug'    => true,
            'database' => $this->getDatabaseConfiguration(),
            'url'      => 'http://flarum.local',
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

        $dbConfig = $this->getDatabaseConfiguration();

        $pdo = (new MySqlConnector)->connect($dbConfig);
        $db = new MySqlConnection($pdo, $dbConfig['database'], $dbConfig['prefix'], $dbConfig);

        $repository = new DatabaseMigrationRepository($db, 'migrations');
        $migrator = new Migrator($repository, $db, new Filesystem);

        if (! $migrator->getRepository()->repositoryExists()) {
            $migrator->getRepository()->createRepository();
        }

        $migrator->run(__DIR__.'/../../../migrations');

        $db->beginTransaction();
    }
}
