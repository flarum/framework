<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database;

use Exception;
use Flarum\Extension\Extension;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\MySqlConnection;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
class Migrator
{
    /**
     * The migration repository implementation.
     *
     * @var \Flarum\Database\MigrationRepositoryInterface
     */
    protected $repository;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The output interface implementation.
     *
     * @var OutputInterface
     */
    protected $output;
    /**
     * @var ConnectionInterface|MySqlConnection
     */
    protected $connection;

    /**
     * Create a new migrator instance.
     *
     * @param  MigrationRepositoryInterface  $repository
     * @param  ConnectionInterface           $connection
     * @param  Filesystem                    $files
     */
    public function __construct(
        MigrationRepositoryInterface $repository,
        ConnectionInterface $connection,
        Filesystem $files
    ) {
        $this->files = $files;
        $this->repository = $repository;

        if (! ($connection instanceof MySqlConnection)) {
            throw new InvalidArgumentException('Only MySQL connections are supported');
        }

        $this->connection = $connection;

        // Workaround for https://github.com/laravel/framework/issues/1186
        $connection->getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    /**
     * Run the outstanding migrations at a given path.
     *
     * @param  string    $path
     * @param  Extension $extension
     * @return void
     */
    public function run($path, Extension $extension = null)
    {
        $files = $this->getMigrationFiles($path);

        $ran = $this->repository->getRan($extension ? $extension->getId() : null);

        $migrations = array_diff($files, $ran);

        $this->runMigrationList($path, $migrations, $extension);
    }

    /**
     * Run an array of migrations.
     *
     * @param  string    $path
     * @param  array     $migrations
     * @param  Extension $extension
     * @return void
     */
    public function runMigrationList($path, $migrations, Extension $extension = null)
    {
        // First we will just make sure that there are any migrations to run. If there
        // aren't, we will just make a note of it to the developer so they're aware
        // that all of the migrations have been run against this database system.
        if (count($migrations) == 0) {
            $this->note('<info>Nothing to migrate.</info>');

            return;
        }

        // Once we have the array of migrations, we will spin through them and run the
        // migrations "up" so the changes are made to the databases. We'll then log
        // that the migration was run so we don't repeat it next time we execute.
        foreach ($migrations as $file) {
            $this->runUp($path, $file, $extension);
        }
    }

    /**
     * Run "up" a migration instance.
     *
     * @param  string    $path
     * @param  string    $file
     * @param  string    $path
     * @param  Extension $extension
     * @return void
     */
    protected function runUp($path, $file, Extension $extension = null)
    {
        $migration = $this->resolve($path, $file);

        $this->runClosureMigration($migration);

        // Once we have run a migrations class, we will log that it was run in this
        // repository so that we don't try to run it next time we do a migration
        // in the application. A migration repository keeps the migrate order.
        $this->repository->log($file, $extension ? $extension->getId() : null);

        $this->note("<info>Migrated:</info> $file");
    }

    /**
     * Rolls all of the currently applied migrations back.
     *
     * @param  string    $path
     * @param  Extension $extension
     * @return int
     */
    public function reset($path, Extension $extension = null)
    {
        $migrations = array_reverse($this->repository->getRan(
            $extension ? $extension->getId() : null
        ));

        $count = count($migrations);

        if ($count === 0) {
            $this->note('<info>Nothing to rollback.</info>');
        } else {
            foreach ($migrations as $migration) {
                $this->runDown($path, $migration, $extension);
            }
        }

        return $count;
    }

    /**
     * Run "down" a migration instance.
     *
     * @param  string    $path
     * @param  string    $file
     * @param  string    $path
     * @param  Extension $extension
     * @return void
     */
    protected function runDown($path, $file, Extension $extension = null)
    {
        $migration = $this->resolve($path, $file);

        $this->runClosureMigration($migration, 'down');

        // Once we have successfully run the migration "down" we will remove it from
        // the migration repository so it will be considered to have not been run
        // by the application then will be able to fire by any later operation.
        $this->repository->delete($file, $extension ? $extension->getId() : null);

        $this->note("<info>Rolled back:</info> $file");
    }

    /**
     * Runs a closure migration based on the migrate direction.
     *
     * @param        $migration
     * @param string $direction
     * @throws Exception
     */
    protected function runClosureMigration($migration, $direction = 'up')
    {
        if (is_array($migration) && array_key_exists($direction, $migration)) {
            call_user_func($migration[$direction], $this->connection->getSchemaBuilder());
        } else {
            throw new Exception('Migration file should contain an array with up/down.');
        }
    }

    /**
     * Get all of the migration files in a given path.
     *
     * @param  string $path
     * @return array
     */
    public function getMigrationFiles($path)
    {
        $files = $this->files->glob($path.'/*_*.php');

        if ($files === false) {
            return [];
        }

        $files = array_map(function ($file) {
            return str_replace('.php', '', basename($file));
        }, $files);

        // Once we have all of the formatted file names we will sort them and since
        // they all start with a timestamp this should give us the migrations in
        // the order they were actually created by the application developers.
        sort($files);

        return $files;
    }

    /**
     * Resolve a migration instance from a file.
     *
     * @param  string $path
     * @param  string $file
     * @return array
     */
    public function resolve($path, $file)
    {
        $migration = "$path/$file.php";

        if ($this->files->exists($migration)) {
            return $this->files->getRequire($migration);
        }
    }

    /**
     * Initialize the Flarum database from a schema dump.
     *
     * @param string $path to the directory containing the dump.
     */
    public function installFromSchema(string $path)
    {
        $schemaPath = "$path/install.dump";

        $startTime = microtime(true);

        $dump = file_get_contents($schemaPath);

        $this->connection->getSchemaBuilder()->disableForeignKeyConstraints();

        foreach (explode(';', $dump) as $statement) {
            $statement = trim($statement);

            if (empty($statement) || substr($statement, 0, 2) === '/*') {
                continue;
            }

            $statement = str_replace(
                'db_prefix_',
                $this->connection->getTablePrefix(),
                $statement
            );
            $this->connection->statement($statement);
        }

        $this->connection->getSchemaBuilder()->enableForeignKeyConstraints();

        $runTime = number_format((microtime(true) - $startTime) * 1000, 2);
        $this->note('<info>Loaded stored database schema.</info> ('.$runTime.'ms)');
    }

    /**
     * Set the output implementation that should be used by the console.
     *
     * @param OutputInterface $output
     * @return $this
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Write a note to the conosle's output.
     *
     * @param string $message
     * @return void
     */
    protected function note($message)
    {
        if ($this->output) {
            $this->output->writeln($message);
        }
    }

    /**
     * Determine if the migration repository exists.
     *
     * @return bool
     */
    public function repositoryExists()
    {
        return $this->repository->repositoryExists();
    }
}
