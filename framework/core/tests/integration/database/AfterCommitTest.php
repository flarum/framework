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
 * Note: the integration harness wraps each test in an open transaction that is
 * rolled back on tearDown, so a registered afterCommit callback is (correctly)
 * deferred to that never-committed transaction and cannot be observed firing
 * here. These tests therefore assert the wiring the fix establishes and that
 * afterCommit no longer throws; the callback-execution semantics themselves are
 * Illuminate's own, guaranteed once a manager is attached.
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
            // Registered against the harness's open transaction, so it is
            // deferred (not run) rather than throwing.
            $connection->afterCommit(function () {
                // no-op
            });
        } catch (RuntimeException $e) {
            $this->fail('Connection::afterCommit() threw: '.$e->getMessage());
        }

        $this->addToAssertionCount(1);
    }
}
