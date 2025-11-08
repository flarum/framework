<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\queue;

use Flarum\Testing\integration\ConsoleTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Exception\CommandNotFoundException;

class QueueCommandTest extends ConsoleTestCase
{
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
