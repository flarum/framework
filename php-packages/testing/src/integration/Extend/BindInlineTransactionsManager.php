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
use Flarum\Testing\integration\Setup\InlineTransactionsManager;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;

/**
 * Rebind `db.transactions` to a manager that runs after-commit callbacks inline
 * so they are observable in tests. Must be applied before the connection is
 * first resolved (i.e. before BeginTransactionAndSetDatabase), because
 * DatabaseManager::configure() attaches whatever `db.transactions` resolves to
 * at that point.
 *
 * @see InlineTransactionsManager
 * @see \Flarum\Tests\integration\database\AfterCommitListenerTest
 */
class BindInlineTransactionsManager implements ExtenderInterface
{
    public function extend(Container $container, ?Extension $extension = null): void
    {
        $manager = new InlineTransactionsManager();

        $container->instance('db.transactions', $manager);

        // The connection may already have been resolved (and had the original
        // manager attached by DatabaseManager::configure()), so attach ours to
        // it directly. This covers Connection::afterCommit() as well as the
        // event dispatcher, which reads db.transactions live.
        $connection = $container->make(ConnectionInterface::class);

        if ($connection instanceof Connection) {
            $connection->setTransactionManager($manager);
        }
    }
}
