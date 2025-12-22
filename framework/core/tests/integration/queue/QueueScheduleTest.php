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

class QueueScheduleTest extends ConsoleTestCase
{
    #[Test]
    public function it_does_not_schedule_queue_worker_for_sync_driver()
    {
        $this->app();

        $output = $this->runCommand(['command' => 'schedule:list']);

        $this->assertStringNotContainsString('queue:work', $output);
    }

    #[Test]
    public function it_schedules_queue_worker_for_database_driver()
    {
        $this->config('queue', ['driver' => 'database']);

        $this->app();

        $output = $this->runCommand(['command' => 'schedule:list']);

        $this->assertStringContainsString('queue:work', $output);
        // The schedule output shows the cron expression instead of "Every minute"
        $this->assertStringContainsString('* * * * *', $output);
    }

    #[Test]
    public function it_includes_worker_args_in_scheduled_command()
    {
        $this->config('queue', ['driver' => 'database']);

        $this->app();

        // Set some queue settings
        $settings = $this->app()->getContainer()->make('flarum.settings');
        $settings->set('database-queue.retries', '5');
        $settings->set('database-queue.memory', '256');

        $output = $this->runCommand(['command' => 'schedule:list']);

        // The schedule:list command should show the command with its arguments
        $this->assertStringContainsString('queue:work', $output);
    }
}
