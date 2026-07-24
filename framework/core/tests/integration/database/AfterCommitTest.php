<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\database;

use Flarum\Testing\integration\TestCase;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseTransactionsManager;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use RuntimeException;

/**
 * Regression test for flarum/framework#4787.
 *
 * Flarum's DatabaseServiceProvider historically omitted the `db.transactions`
 * binding, so connections never received a DatabaseTransactionsManager and any
 * call to Connection::afterCommit() threw "Transactions Manager has not been
 * set." Binding it lets Illuminate's DatabaseManager::configure() attach the
 * manager to every connection.
 *
 * These tests assert the wiring the fix establishes: that `db.transactions` is
 * bound and attached to the connection, and that `Connection::afterCommit()`
 * no longer throws.
 *
 * The integration harness wraps each test in an open transaction that is rolled
 * back on tearDown. So that after-commit listeners remain observable in tests
 * rather than being discarded with that rollback, the harness runs their
 * callbacks inline; see InlineTransactionsManager and AfterCommitListenerTest
 * (flarum/framework#4814).
 */
class AfterCommitTest extends TestCase
{
    private function connection(): ConnectionInterface
    {
        return $this->app()->getContainer()->make(ConnectionInterface::class);
    }

    #[Test]
    public function transactions_manager_is_bound(): void
    {
        $this->assertInstanceOf(
            DatabaseTransactionsManager::class,
            $this->app()->getContainer()->make('db.transactions')
        );
    }

    #[Test]
    public function connection_receives_the_transactions_manager(): void
    {
        $connection = $this->connection();

        $property = new ReflectionProperty(Connection::class, 'transactionsManager');
        $property->setAccessible(true);

        $this->assertInstanceOf(
            DatabaseTransactionsManager::class,
            $property->getValue($connection),
            'The connection should have a transactions manager attached by DatabaseManager::configure().'
        );
    }

    #[Test]
    public function after_commit_does_not_throw(): void
    {
        $connection = $this->connection();

        try {
            $connection->afterCommit(function () {
                // no-op
            });
        } catch (RuntimeException $e) {
            $this->fail('Connection::afterCommit() threw: '.$e->getMessage());
        }

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function after_commit_callback_runs_in_tests(): void
    {
        $ran = false;

        // The test harness runs after-commit callbacks inline (see
        // InlineTransactionsManager) so they don't get discarded with the
        // harness's rolled-back transaction. See flarum/framework#4814.
        $this->connection()->afterCommit(function () use (&$ran) {
            $ran = true;
        });

        $this->assertTrue($ran, 'Connection::afterCommit() callback should run during a test.');
    }
}
