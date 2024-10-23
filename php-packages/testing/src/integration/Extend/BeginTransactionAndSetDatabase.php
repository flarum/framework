<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Testing\integration\Extend;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;

class BeginTransactionAndSetDatabase implements ExtenderInterface
{
    /**
     * A callback to set the database connection object on the test case.
     */
    protected $setDbOnTestCase;

    public function __construct(callable $setDbOnTestCase)
    {
        $this->setDbOnTestCase = $setDbOnTestCase;
    }

    public function extend(Container $container, Extension $extension = null): void
    {
        /** @var Connection $db */
        $db = $container->make(ConnectionInterface::class);

        // SQLite requires this be done outside a transaction.
        if ($db->getDriverName() === 'sqlite') {
            $db->getSchemaBuilder()->disableForeignKeyConstraints();
        }

        $db->beginTransaction();

        ($this->setDbOnTestCase)($db);
    }
}
