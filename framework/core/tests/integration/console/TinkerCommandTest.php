<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\console;

use Flarum\Testing\integration\ConsoleTestCase;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class TinkerCommandTest extends ConsoleTestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    /**
     * Run a command and return both its output and exit code, since the base
     * helper discards the code and the --execute contract depends on it.
     *
     * @return array{output: string, status: int}
     */
    protected function runCommandWithStatus(array $inputArray): array
    {
        $output = new BufferedOutput();
        $status = $this->console()->run(new ArrayInput($inputArray), $output);

        return ['output' => trim($output->fetch()), 'status' => $status];
    }

    #[Test]
    public function execute_option_evaluates_code_and_succeeds()
    {
        $result = $this->runCommandWithStatus([
            'command' => 'tinker',
            '--execute' => '1 + 1',
        ]);

        $this->assertStringContainsString('2', $result['output']);
        $this->assertEquals(Command::SUCCESS, $result['status']);
    }

    #[Test]
    public function execute_option_returns_failure_when_code_throws()
    {
        $result = $this->runCommandWithStatus([
            'command' => 'tinker',
            '--execute' => 'throw new \Exception("boom")',
        ]);

        // The error is surfaced...
        $this->assertStringContainsString('boom', $result['output']);
        // ...and the exit code is non-zero, so scripts can detect the failure.
        $this->assertEquals(Command::FAILURE, $result['status']);
    }

    #[Test]
    public function models_can_be_referenced_by_short_name()
    {
        $result = $this->runCommandWithStatus([
            'command' => 'tinker',
            '--execute' => 'User::where("username", "normal")->exists()',
        ]);

        $this->assertEquals(Command::SUCCESS, $result['status']);
        // Resolving the seeded user by short-name `User` proves the alias
        // autoloader mapped it to Flarum\User\User without a qualified name.
        $this->assertStringContainsString('true', $result['output']);
    }

    #[Test]
    public function reaching_for_a_laravel_facade_shows_a_hint()
    {
        $result = $this->runCommandWithStatus([
            'command' => 'tinker',
            '--execute' => 'DB::table("users")',
        ]);

        $this->assertStringContainsString('$db', $result['output']);
    }

    #[Test]
    public function command_can_run_more_than_once_in_one_process()
    {
        // The model-alias autoloader must be unregistered between runs;
        // otherwise the second run re-aliases `User` and PHP warns. Running
        // twice here would surface that as an error/leak.
        $first = $this->runCommandWithStatus([
            'command' => 'tinker',
            '--execute' => 'User::count()',
        ]);
        $second = $this->runCommandWithStatus([
            'command' => 'tinker',
            '--execute' => 'User::count()',
        ]);

        $this->assertEquals(Command::SUCCESS, $first['status']);
        $this->assertEquals(Command::SUCCESS, $second['status']);
    }
}
