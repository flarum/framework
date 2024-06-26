<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Foundation\Config;
use Flarum\Foundation\Paths;
use Illuminate\Database\Connection;
use Illuminate\Database\MySqlConnection;
use Symfony\Component\Console\Command\Command;

class GenerateDumpCommand extends AbstractCommand
{
    public function __construct(
        protected Connection $connection,
        protected Config $config,
        protected Paths $paths
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('schema:dump')
            ->setDescription('Dump DB schema');
    }

    protected function fire(): int
    {
        $driver = $this->config['database.driver'];
        $dumpPath = __DIR__."/../../../migrations/$driver-install.dump";
        /** @var Connection&MySqlConnection $connection */
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
                if (str_contains($line, $excludeMigrationId)) {
                    continue 2;
                }
            }
            $newDump[] = $line;
        }

        file_put_contents($dumpPath, implode($newDump));

        return Command::SUCCESS;
    }
}
