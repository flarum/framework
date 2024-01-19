<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;

class DatabaseMigrationRepository implements MigrationRepositoryInterface
{
    public function __construct(
        protected ConnectionInterface $connection,
        protected string $table
    ) {
    }

    public function getRan(?string $extension = null): array
    {
        return $this->table()
                ->where('extension', $extension)
                ->orderBy('migration', 'asc')
                ->pluck('migration')
                ->toArray();
    }

    public function log(string $file, ?string $extension = null): void
    {
        $record = ['migration' => $file, 'extension' => $extension];

        $this->table()->insert($record);
    }

    public function delete(string $file, ?string $extension = null): void
    {
        $query = $this->table()->where('migration', $file);

        if (is_null($extension)) {
            $query->whereNull('extension');
        } else {
            $query->where('extension', $extension);
        }

        $query->delete();
    }

    public function repositoryExists(): bool
    {
        $schema = $this->connection->getSchemaBuilder();

        return $schema->hasTable($this->table);
    }

    /**
     * Get a query builder for the migration table.
     */
    protected function table(): Builder
    {
        return $this->connection->table($this->table);
    }
}
