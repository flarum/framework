<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database;

use Flarum\Database\Exception\MigrationKeyMissing;
use Flarum\Extension\Extension;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
class Migrator
{
    protected ?OutputInterface $output = null;

    public function __construct(
        protected MigrationRepositoryInterface $repository,
        protected ConnectionInterface $connection,
        protected Filesystem $files
    ) {
    }

    /**
     * Run the outstanding migrations at a given path.
     */
    public function run(string $path, ?Extension $extension = null): void
    {
        $files = $this->getMigrationFiles($path);

        $ran = $this->repository->getRan($extension?->getId());

        $migrations = array_diff($files, $ran);

        $this->runMigrationList($path, $migrations, $extension);
    }

    public function runMigrationList(string $path, array $migrations, ?Extension $extension = null): void
    {
        // First we will just make sure that there are any migrations to run. If there
        // aren't, we will just make a note of it to the developer so they're aware
        // that all the migrations have been run against this database system.
        if (count($migrations) == 0) {
            $this->note('<info>Nothing to migrate.</info>');

            return;
        }

        // Once we have the array of migrations, we will spin through them and run the
        // migrations "up" so the changes are made to the databases. We'll then log
        // that the migration was run so we don't repeat it next time we execute.
        $this->runUpMigrations($migrations, $path, $extension);
    }

    protected function runUpMigrations(array $migrations, string $path, ?Extension $extension = null): void
    {
        $process = function () use ($migrations, $path, $extension) {
            foreach ($migrations as $migration) {
                $this->runUp($path, $migration, $extension);
            }
        };

        // PgSQL allows DDL statements in transactions.
        if ($this->connection->getDriverName() === 'pgsql') {
            $this->connection->transaction($process);
        } else {
            $process();
        }
    }

    protected function runDownMigrations(array $migrations, string $path, ?Extension $extension = null): void
    {
        $process = function () use ($migrations, $path, $extension) {
            foreach ($migrations as $migration) {
                $this->runDown($path, $migration, $extension);
            }
        };

        // PgSQL allows DDL statements in transactions.
        if ($this->connection->getDriverName() === 'pgsql') {
            $this->connection->transaction($process);
        } else {
            $process();
        }
    }

    /**
     * Run "up" a migration instance.
     */
    protected function runUp(string $path, string $file, ?Extension $extension = null): void
    {
        $this->resolveAndRunClosureMigration($path, $file);

        // Once we have run a migrations class, we will log that it was run in this
        // repository so that we don't try to run it next time we do a migration
        // in the application. A migration repository keeps the migrate order.
        $this->repository->log($file, $extension?->getId());

        $this->note("<info>Migrated:</info> $file");
    }

    /**
     * Rolls all the currently applied migrations back.
     */
    public function reset(string $path, ?Extension $extension = null): int
    {
        $migrations = array_reverse($this->repository->getRan(
            $extension ? $extension->getId() : null
        ));

        $count = count($migrations);

        if ($count === 0) {
            $this->note('<info>Nothing to rollback.</info>');
        } else {
            $this->runDownMigrations($migrations, $path, $extension);
        }

        return $count;
    }

    /**
     * Run "down" a migration instance.
     */
    protected function runDown(string $path, string $file, ?Extension $extension = null): void
    {
        $this->resolveAndRunClosureMigration($path, $file, 'down');

        // Once we have successfully run the migration "down" we will remove it from
        // the migration repository, so it will be considered to have not been run
        // by the application then will be able to fire by any later operation.
        $this->repository->delete($file, $extension?->getId());

        $this->note("<info>Rolled back:</info> $file");
    }

    /**
     * Runs a closure migration based on the migrate direction.
     *
     * @throws MigrationKeyMissing
     */
    protected function runClosureMigration(array $migration, string $direction = 'up'): void
    {
        if (array_key_exists($direction, $migration)) {
            call_user_func($migration[$direction], $this->connection->getSchemaBuilder());
        } else {
            throw new MigrationKeyMissing($direction);
        }
    }

    /**
     * Resolves and run a migration and assign the filename to the exception if needed.
     *
     * @throws MigrationKeyMissing|FileNotFoundException
     */
    protected function resolveAndRunClosureMigration(string $path, string $file, string $direction = 'up'): void
    {
        $migration = $this->resolve($path, $file);

        try {
            $this->runClosureMigration($migration, $direction);
        } catch (MigrationKeyMissing $exception) {
            throw $exception->withFile("$path/$file.php");
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
     * @throws FileNotFoundException|RuntimeException
     */
    public function resolve(string $path, string $file): array
    {
        $migration = "$path/$file.php";

        if ($this->files->exists($migration)) {
            $migrationContents = $this->files->getRequire($migration);

            if (! is_array($migrationContents)) {
                throw new RuntimeException('Migration must return an array with up and down keys');
            }

            return $migrationContents;
        }

        return [];
    }

    /**
     * Initialize the Flarum database from a schema dump.
     *
     * @param string $path to the directory containing the dump.
     */
    public function installFromSchema(string $path, string $driver): bool
    {
        $schemaPath = "$path/$driver-install.dump";

        if (! file_exists($schemaPath)) {
            return false;
        }

        $startTime = microtime(true);

        $dump = file_get_contents($schemaPath);

        $dumpWithoutComments = preg_replace('/^--.*$/m', '', $dump);

        $this->connection->getSchemaBuilder()->disableForeignKeyConstraints();

        foreach (explode(';', $dumpWithoutComments) as $statement) {
            $statement = trim($statement);

            if (empty($statement) || str_starts_with($statement, '/*')) {
                continue;
            }

            $statement = str_replace(
                'db_prefix_',
                $this->connection->getTablePrefix() ?? '',
                $statement
            );
            $this->connection->statement($statement);
        }

        if ($driver === 'pgsql') {
            $this->connection->statement('SELECT pg_catalog.set_config(\'search_path\', \'public\', false)');
        }

        $this->connection->getSchemaBuilder()->enableForeignKeyConstraints();

        $runTime = number_format((microtime(true) - $startTime) * 1000, 2);
        $this->note('<info>Loaded stored database schema.</info> ('.$runTime.'ms)');

        return true;
    }

    public function setOutput(OutputInterface $output): static
    {
        $this->output = $output;

        return $this;
    }

    protected function note(string $message): void
    {
        $this->output?->writeln($message);
    }

    /**
     * Get the migration repository instance.
     */
    public function getRepository(): MigrationRepositoryInterface
    {
        return $this->repository;
    }

    public function repositoryExists(): bool
    {
        return $this->repository->repositoryExists();
    }
}
