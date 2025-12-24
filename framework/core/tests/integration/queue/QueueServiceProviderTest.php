<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\queue;

use Flarum\Extend;
use Flarum\Testing\integration\TestCase;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Queue\SyncQueue;
use PHPUnit\Framework\Attributes\Test;

class QueueServiceProviderTest extends TestCase
{
    #[Test]
    public function it_uses_sync_queue_by_default()
    {
        $this->app();

        $queue = $this->app()->getContainer()->make(Queue::class);

        $this->assertInstanceOf(SyncQueue::class, $queue);
    }

    #[Test]
    public function it_uses_database_queue_when_configured()
    {
        $this->config('queue', ['driver' => 'database']);

        $this->app();

        $queue = $this->app()->getContainer()->make(Queue::class);

        $this->assertInstanceOf(DatabaseQueue::class, $queue);
    }

    #[Test]
    public function it_allows_extensions_to_override_queue()
    {
        $this->extend(
            (new Extend\ServiceProvider())
                ->register(CustomQueueServiceProvider::class)
        );

        $this->app();

        $queue = $this->app()->getContainer()->make(Queue::class);

        // Should be our custom sync queue, not the default
        $this->assertNotEquals(SyncQueue::class, get_class($queue));
        $this->assertInstanceOf(SyncQueue::class, $queue);
    }

    #[Test]
    public function it_does_not_register_queue_commands_for_sync_driver()
    {
        $this->app();

        $commands = $this->app()->getContainer()->make('flarum.console.commands');

        $commandNames = array_map(function ($command) {
            return is_string($command) ? $command : get_class($command);
        }, $commands);

        // Queue commands should not be registered for sync driver
        $this->assertNotContains(\Flarum\Queue\Console\WorkCommand::class, $commandNames);
    }

    #[Test]
    public function it_registers_queue_commands_for_database_driver()
    {
        $this->config('queue', ['driver' => 'database']);

        $this->app();

        $commands = $this->app()->getContainer()->make('flarum.console.commands');

        $commandNames = array_map(function ($command) {
            return is_string($command) ? $command : get_class($command);
        }, $commands);

        // Queue commands should be registered for database driver
        $this->assertContains(\Flarum\Queue\Console\WorkCommand::class, $commandNames);
        $this->assertContains(\Illuminate\Queue\Console\RestartCommand::class, $commandNames);
        $this->assertContains(\Illuminate\Queue\Console\RetryCommand::class, $commandNames);
    }

    #[Test]
    public function it_uses_null_failed_job_provider_for_sync_queue()
    {
        $this->app();

        $failer = $this->app()->getContainer()->make('queue.failer');

        $this->assertInstanceOf(\Illuminate\Queue\Failed\NullFailedJobProvider::class, $failer);
    }

    #[Test]
    public function it_uses_database_failed_job_provider_for_database_queue()
    {
        $this->config('queue', ['driver' => 'database']);

        $this->app();

        $failer = $this->app()->getContainer()->make('queue.failer');

        $this->assertInstanceOf(\Flarum\Queue\DatabaseUuidFailedJobProvider::class, $failer);
    }
}

class CustomQueueServiceProvider extends \Flarum\Foundation\AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->extend('flarum.queue.connection', function ($queue, $container) {
            $customQueue = new class extends SyncQueue {
                // Custom queue implementation for testing
            };
            $customQueue->setContainer($container);

            return $customQueue;
        });
    }
}
