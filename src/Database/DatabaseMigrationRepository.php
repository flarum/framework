<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database;

use Illuminate\Database\ConnectionInterface;

class DatabaseMigrationRepository implements MigrationRepositoryInterface
{
    /**
     * The name of the database connection to use.
     *
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * The name of the migration table.
     *
     * @var string
     */
    protected $table;

    /**
     * Create a new database migration repository instance.
     *
     * @param  \Illuminate\Database\ConnectionInterface $connection
     * @param  string                                   $table
     */
    public function __construct(ConnectionInterface $connection, $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     * Get the ran migrations.
     *
     * @param string $extension
     * @return array
     */
    public function getRan($extension = null)
    {
        return $this->table()
                ->where('extension', $extension)
                ->orderBy('migration', 'asc')
                ->pluck('migration')
                ->toArray();
    }

    /**
     * Log that a migration was run.
     *
     * @param string $file
     * @param string $extension
     * @return void
     */
    public function log($file, $extension = null)
    {
        $record = ['migration' => $file, 'extension' => $extension];

        $this->table()->insert($record);
    }

    /**
     * Remove a migration from the log.
     *
     * @param string $file
     * @param string $extension
     * @return void
     */
    public function delete($file, $extension = null)
    {
        $query = $this->table()->where('migration', $file);

        if (is_null($extension)) {
            $query->whereNull('extension');
        } else {
            $query->where('extension', $extension);
        }

        $query->delete();
    }

    /**
     * Determine if the migration repository exists.
     *
     * @return bool
     */
    public function repositoryExists()
    {
        $schema = $this->connection->getSchemaBuilder();

        return $schema->hasTable($this->table);
    }

    /**
     * Get a query builder for the migration table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table()
    {
        return $this->connection->table($this->table);
    }
}
