<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\integration;

use Flarum\ExtensionManager\Task\Task;
use PHPUnit\Framework\Attributes\Test;

class TaskTest extends TestCase
{
    #[Test]
    public function a_pending_task_can_be_built_without_output()
    {
        $this->app();

        $task = Task::build(Task::UPDATE_GLOBAL, null);

        $task = Task::query()->findOrFail($task->id);

        $this->assertEquals(Task::PENDING, $task->status);
        $this->assertSame('', $task->output);
    }

    #[Test]
    public function a_task_with_a_long_command_can_be_ended()
    {
        $this->app();

        $task = Task::build(Task::UPDATE_GLOBAL, null);

        $command = 'update --prefer-dist --no-dev -a --with-all-dependencies';

        $this->assertGreaterThan(50, strlen($command));

        $task->command = $command;

        $this->assertTrue($task->end(true));

        $task = Task::query()->findOrFail($task->id);

        $this->assertEquals(Task::SUCCESS, $task->status);
        $this->assertEquals($command, $task->command);
    }
}
