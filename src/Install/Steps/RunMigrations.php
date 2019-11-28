<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install\Steps;

use Flarum\Database\DatabaseMigrationRepository;
use Flarum\Database\Migrator;
use Flarum\Install\Step;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Filesystem\Filesystem;

class RunMigrations implements Step
{
    /**
     * @var ConnectionInterface
     */
    private $database;

    /**
     * @var string
     */
    private $path;

    public function __construct(ConnectionInterface $database, $path)
    {
        $this->database = $database;
        $this->path = $path;
    }

    public function getMessage()
    {
        return 'Running migrations';
    }

    public function run()
    {
        $migrator = $this->getMigrator();

        $migrator->getRepository()->createRepository();
        $migrator->run($this->path);
    }

    private function getMigrator()
    {
        $repository = new DatabaseMigrationRepository(
            $this->database, 'migrations'
        );
        $files = new Filesystem;

        return new Migrator($repository, $this->database, $files);
    }
}
