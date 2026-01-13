<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Queue;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;

class DatabaseUuidFailedJobProvider extends \Illuminate\Queue\Failed\DatabaseUuidFailedJobProvider
{
    public function __construct(ConnectionResolverInterface $resolver, $database, $table, protected ConnectionInterface $connection)
    {
        parent::__construct($resolver, $database, $table);
    }

    /**
     * Get a new query builder instance for the table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function getTable()
    {
        return $this->connection->table($this->table);
    }
}
