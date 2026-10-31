<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\queue;

use Flarum\Queue\QueueFactory;
use Flarum\Testing\integration\ConsoleTestCase;
use Illuminate\Contracts\Queue\Queue;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Exception\CommandNotFoundException;

class QueueCommandTest extends ConsoleTestCase
{
    /**
     * `queue:resume` (no argument) must recover a queue that is paused under a
     * known name even when the pause was never recorded in the tracking list —
     * which is exactly the state the queue:pause fatal leaves behind. Resuming
     * only the tracked queues would strand it. So resume also covers every
     * known queue name (`flarum.queue.queues`), not just the tracking list.
     */
    #[Test]
    public function resume_recovers_a_known_queue_paused_outside_the_tracking_list()
    {
        $this->config('queue', ['driver' => 'database']);

        $this->extend(
            (new \Flarum\Extend\Console())->command(\Flarum\Queue\Console\ResumeCommand::class),
            (new \Flarum\Extend\ServiceProvider())->register(KnownQueuesServiceProvider::class)
        );

        $this->app();

        $container = $this->app()->getContainer();
        $factory = $container->make(QueueFactory::class);
        $name = $container->make(Queue::class)->getConnectionName();
        $cache = $container->make('cache.store');

        // Pause the `media` queue the way the fatal leaves it: the cache key is
        // set, but nothing was written to the tracking list.
        $cache->forever("illuminate:queue:paused:{$name}:media", true);

        $this->assertTrue($factory->isPaused($name, 'media'));
        $this->assertNotContains('media', $factory->pausedQueues($name));

        $this->runCommand(['command' => 'queue:resume']);

        $this->assertFalse(
            $factory->isPaused($name, 'media'),
            'A known queue paused outside the tracking list must still be resumed by `queue:resume`.'
        );
    }

    #[Test]
    public function queue_commands_dont_exist_with_sync_driver()
    {
        $this->app();

        $this->expectException(CommandNotFoundException::class);
        $this->runCommand(['command' => 'queue:work']);
    }

    #[Test]
    public function queue_work_command_exists_with_database_driver()
    {
        $this->config('queue', ['driver' => 'database']);

        $this->app();

        // Just test that the command is registered (it will fail without actual jobs, but won't throw CommandNotFoundException)
        try {
            $output = $this->runCommand([
                'command' => 'queue:work',
                '--stop-when-empty' => true,
            ]);
            // If we get here, command exists and ran (even if empty queue)
            $this->assertTrue(true);
        } catch (CommandNotFoundException $e) {
            $this->fail('queue:work command should be registered with database driver');
        }
    }

    #[Test]
    public function queue_restart_command_exists_with_database_driver()
    {
        $this->config('queue', ['driver' => 'database']);

        $this->app();

        $output = $this->runCommand(['command' => 'queue:restart']);

        $this->assertStringContainsString('Broadcasting queue restart signal', $output);
    }

    #[Test]
    public function queue_list_failed_command_exists_with_database_driver()
    {
        $this->config('queue', ['driver' => 'database']);

        $this->app();

        // This command should run without error even with no failed jobs
        $output = $this->runCommand(['command' => 'queue:failed']);

        // Should not throw an exception
        $this->assertTrue(true);
    }
}

class KnownQueuesServiceProvider extends \Flarum\Foundation\AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->extend('flarum.queue.queues', function ($queues) {
            return array_values(array_unique(array_merge((array) $queues, ['media'])));
        });
    }
}
