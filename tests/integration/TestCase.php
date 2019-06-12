<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\integration;

use Flarum\Foundation\InstalledSite;
use Illuminate\Database\ConnectionInterface;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Boot the Flarum app
        $this->app();
    }

    /**
     * @var \Flarum\Foundation\InstalledApp
     */
    protected $app;

    /**
     * @var \Psr\Http\Server\RequestHandlerInterface
     */
    protected $server;

    /**
     * @return \Flarum\Foundation\InstalledApp
     */
    protected function app()
    {
        if (is_null($this->app)) {
            $site = new InstalledSite(
                [
                    'base' => __DIR__.'/tmp',
                    'vendor' => __DIR__.'/../../vendor',
                    'public' => __DIR__.'/tmp/public',
                    'storage' => __DIR__.'/tmp/storage',
                ],
                include __DIR__.'/tmp/config.php'
            );

            $this->app = $site->bootApp();
            $this->server = $this->app->getRequestHandler();
        }

        return $this->app;
    }

    protected $database;

    protected function database(): ConnectionInterface
    {
        if (is_null($this->database)) {
            $this->database = $this->app()->getContainer()->make(
                ConnectionInterface::class
            );
        }

        return $this->database;
    }

    protected function prepareDatabase(array $tableData)
    {
        // We temporarily disable foreign key checks to simplify this process.
        $this->database()->getSchemaBuilder()->disableForeignKeyConstraints();

        // First, truncate all referenced tables so that they are empty.
        foreach (array_keys($tableData) as $table) {
            $this->database()->table($table)->truncate();
        }

        // Then, insert all rows required for this test case.
        foreach ($tableData as $table => $rows) {
            $this->database()->table($table)->insert($rows);
        }

        // And finally, turn on foreign key checks again.
        $this->database()->getSchemaBuilder()->enableForeignKeyConstraints();
    }
}
