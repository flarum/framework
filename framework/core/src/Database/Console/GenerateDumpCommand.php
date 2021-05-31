<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Foundation\Paths;
use Illuminate\Database\Connection;

class GenerateDumpCommand extends AbstractCommand
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Paths
     */
    protected $paths;

    /**
     * @param Connection $connection
     * @param Paths $paths
     */
    public function __construct(Connection $connection, Paths $paths)
    {
        $this->connection = $connection;
        $this->paths = $paths;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('schema:dump')
            ->setDescription('Dump DB schema');
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $dumpPath = __DIR__.'/../../../migrations/install.dump';
        /** @var Connection */
        $connection = resolve('db.connection');

        $connection
            ->getSchemaState()
            ->withMigrationTable($connection->getTablePrefix().'migrations')
            ->handleOutputUsing(function ($type, $buffer) {
                $this->output->write($buffer);
            })
            ->dump($connection, $dumpPath);

        // We need to remove any data migrations, as those won't be captured
        // in the schema dump, and must be run separately.
        $coreDataMigrations = [
            '2018_07_21_000000_seed_default_groups',
            '2018_07_21_000100_seed_default_group_permissions',
        ];

        $newDump = [];
        $dump = file($dumpPath);
        foreach ($dump as $line) {
            foreach ($coreDataMigrations as $excludeMigrationId) {
                if (strpos($line, $excludeMigrationId) !== false) {
                    continue 2;
                }
            }
            $newDump[] = $line;
        }

        file_put_contents($dumpPath, implode($newDump));
    }
}
