<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\console;

use Flarum\Database\Console\MigrateCommand;
use Flarum\Foundation\Paths;
use Flarum\Queue\QueueFactory;
use Flarum\Testing\integration\ConsoleTestCase;
use Illuminate\Contracts\Queue\Factory;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

/**
 * A migration reshapes the schema out from under any queue worker running at
 * the same time. So while migrations run — whether started from the web
 * updater or `php flarum migrate` — the queue is paused, and resumed the
 * moment they finish.
 *
 * That a paused worker actually stops popping jobs is proven by
 * {@see \Flarum\Tests\integration\queue\QueuePauseTest}. These tests cover the
 * one thing the migrate command is responsible for: setting and clearing the
 * pause flag at the right times, and only when there is work to do.
 */
class MigrateCommandQueuePauseTest extends ConsoleTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->config('queue', ['driver' => 'database']);
    }

    private function factory(): QueueFactory
    {
        $factory = $this->app()->getContainer()->make(Factory::class);

        $this->assertInstanceOf(QueueFactory::class, $factory);

        return $factory;
    }

    /**
     * A migrate command with its migration body replaced, so the test decides
     * whether there is pending work and whether it fails, while the real
     * pause/resume wrapping runs unchanged. It records whether the queue was
     * paused at the moment the body ran.
     */
    private function stubCommand(bool $pending, bool $fail = false): TestMigrateCommand
    {
        $container = $this->app()->getContainer();

        $command = new TestMigrateCommand($container, $container->make(Paths::class));
        $command->pending = $pending;
        $command->fail = $fail;
        $command->factory = $this->factory();

        $this->console()->add($command);

        return $command;
    }

    #[Test]
    public function a_run_with_nothing_pending_leaves_the_queue_alone()
    {
        // `php flarum migrate` is run routinely with nothing to do. Idling
        // every worker for a no-op run would be a regression.
        $command = $this->stubCommand(pending: false);

        $this->runCommand(['command' => 'migrate']);

        $this->assertFalse($command->wasPausedDuringRun, 'A no-op migrate must not pause the queue.');
        $this->assertFalse($this->factory()->isPaused('flarum', '*'));
    }

    #[Test]
    public function a_no_op_run_still_clears_a_pause_armed_before_it()
    {
        // The web updater arms a pause the moment it sees the version drift,
        // before this command runs — and there may be nothing left to migrate
        // by the time it does. The resume must clear that earlier pause anyway,
        // or the updater flow leaves the queue paused forever. This is the real
        // bug that reached the dev forum.
        $this->factory()->pause('flarum', '*');

        $command = $this->stubCommand(pending: false);

        $this->runCommand(['command' => 'migrate']);

        $this->assertFalse(
            $this->factory()->isPaused('flarum', '*'),
            'A no-op migrate must still clear a pause armed before it, or the web updater leaves the queue stuck.'
        );
    }

    #[Test]
    public function a_run_with_pending_work_is_paused_while_it_runs_and_resumed_after()
    {
        $command = $this->stubCommand(pending: true);

        $this->runCommand(['command' => 'migrate']);

        $this->assertTrue($command->wasPausedDuringRun, 'The queue must be paused while migrations run.');
        $this->assertFalse($this->factory()->isPaused('flarum', '*'), 'The queue must be resumed afterwards.');
    }

    #[Test]
    public function the_queue_is_resumed_even_when_the_migration_fails()
    {
        // A failing migration is exactly when a worker must not touch the
        // half-changed schema — but leaving the queue paused forever is its own
        // outage. Resume runs in a finally.
        $command = $this->stubCommand(pending: true, fail: true);

        try {
            $this->runCommand(['command' => 'migrate']);
        } catch (RuntimeException) {
            // The stubbed body throws on purpose.
        }

        $this->assertTrue($command->wasPausedDuringRun, 'The queue must have been paused before the failure.');
        $this->assertFalse($this->factory()->isPaused('flarum', '*'), 'A failed migration must still resume the queue.');
    }
}

/**
 * A migrate command whose real migration body is replaced, so a test can drive
 * the pause/resume wrapping without touching the schema.
 */
class TestMigrateCommand extends MigrateCommand
{
    public bool $pending = false;
    public bool $fail = false;
    public ?QueueFactory $factory = null;
    public bool $wasPausedDuringRun = false;

    protected function hasPendingMigrations(): bool
    {
        return $this->pending;
    }

    protected function runMigrations(): void
    {
        // Observe the pause the moment the (stubbed) migration runs — this is
        // the window a real worker would be in danger.
        $this->wasPausedDuringRun = (bool) $this->factory?->isPaused('flarum', '*');

        if ($this->fail) {
            throw new RuntimeException('Simulated migration failure.');
        }
    }
}
