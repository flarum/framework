<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\console;

use Flarum\Testing\integration\ConsoleTestCase;
use Illuminate\Console\CommandMutex;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class MigrateCommandIsolationTest extends ConsoleTestCase
{
    #[Test]
    public function migrate_runs_normally_with_isolated_option()
    {
        $result = $this->runCommandWithStatus([
            'command' => 'migrate',
            '--isolated' => null,
        ]);

        $this->assertStringContainsString('Migrating Flarum...', $result['output']);
        $this->assertStringContainsString('DONE.', $result['output']);
        $this->assertEquals(Command::SUCCESS, $result['status']);
    }

    #[Test]
    public function isolated_run_is_skipped_while_mutex_is_held()
    {
        $command = $this->migrateCommand();

        $this->assertTrue($this->mutex()->create($command));

        try {
            $result = $this->runCommandWithStatus([
                'command' => 'migrate',
                '--isolated' => null,
            ]);

            $this->assertStringContainsString('The [migrate] command is already running.', $result['output']);
            $this->assertStringNotContainsString('DONE.', $result['output']);

            $this->assertEquals(Command::SUCCESS, $result['status']);
        } finally {
            $this->mutex()->forget($command);
        }
    }

    #[Test]
    public function isolated_exit_code_can_be_customized()
    {
        $command = $this->migrateCommand();

        $this->assertTrue($this->mutex()->create($command));

        try {
            $result = $this->runCommandWithStatus([
                'command' => 'migrate',
                '--isolated' => '13',
            ]);

            $this->assertStringContainsString('The [migrate] command is already running.', $result['output']);
            $this->assertEquals(13, $result['status']);
        } finally {
            $this->mutex()->forget($command);
        }
    }

    #[Test]
    public function mutex_is_released_after_an_isolated_run()
    {
        $first = $this->runCommandWithStatus([
            'command' => 'migrate',
            '--isolated' => null,
        ]);
        $second = $this->runCommandWithStatus([
            'command' => 'migrate',
            '--isolated' => null,
        ]);

        $this->assertStringContainsString('DONE.', $first['output']);
        $this->assertStringContainsString('DONE.', $second['output']);
    }

    #[Test]
    public function non_isolated_run_ignores_the_mutex()
    {
        $command = $this->migrateCommand();

        $this->assertTrue($this->mutex()->create($command));

        try {
            $result = $this->runCommandWithStatus([
                'command' => 'migrate',
            ]);

            $this->assertStringContainsString('DONE.', $result['output']);
            $this->assertEquals(Command::SUCCESS, $result['status']);
        } finally {
            $this->mutex()->forget($command);
        }
    }

    protected function runCommandWithStatus(array $inputArray): array
    {
        $output = new BufferedOutput();
        $status = $this->console()->run(new ArrayInput($inputArray), $output);

        return ['output' => trim($output->fetch()), 'status' => $status];
    }

    protected function mutex(): CommandMutex
    {
        return $this->app()->getContainer()->make(CommandMutex::class);
    }

    protected function migrateCommand(): Command
    {
        return $this->console()->find('migrate');
    }
}
