<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Run\Run;
use Flarum\ExtensionManager\Run\RunStore;
use Flarum\ExtensionManager\Run\StepRunner;
use Flarum\ExtensionManager\Run\Steps;
use PHPUnit\Framework\TestCase;

/**
 * The driver's promise: an update completes however many times it is
 * interrupted, and it never lies about what it is doing.
 */
class StepRunnerTest extends TestCase
{
    private string $dir;
    private int $clock = 1000;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-run-'.bin2hex(random_bytes(6));
        mkdir($this->dir, 0775, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir.'/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->dir);
    }

    public function test_an_update_completes_one_step_at_a_time(): void
    {
        [$runner, $steps] = $this->make(['plan' => ['resolve'], 'fetch' => ['a', 'b'], 'apply' => ['a', 'b'], 'finalise' => ['autoload']]);
        $runner->begin('r1');

        $guard = 0;
        do {
            $run = $runner->step('r1');
        } while (! $run->isFinished() && ++$guard < 100);

        $this->assertSame(Run::DONE, $run->state);
        $this->assertSame(
            ['plan:resolve', 'fetch:a', 'fetch:b', 'apply:a', 'apply:b', 'finalise:autoload'],
            $steps->done,
            'every item ran exactly once, in order'
        );
    }

    public function test_no_single_step_does_more_than_one_item(): void
    {
        // 🚨 The whole reason this exists. If a step ever did two things, a host
        // with a 30 second ceiling would eventually be cut off mid-item again.
        [$runner, $steps] = $this->make(['plan' => [], 'fetch' => ['a', 'b', 'c'], 'apply' => [], 'finalise' => []]);
        $runner->begin('r1');

        $counts = [];
        for ($i = 0; $i < 12; $i++) {
            $before = count($steps->done);
            $run = $runner->step('r1');
            $counts[] = count($steps->done) - $before;

            if ($run->isFinished()) {
                break;
            }
        }

        $this->assertLessThanOrEqual(1, max($counts), 'a step did more than one item of work');
    }

    public function test_a_run_resumes_from_a_completely_fresh_process_state(): void
    {
        // Standing in for the tab being closed and reopened, or a cron tick
        // arriving in a new process: nothing is held in memory between calls.
        [$_, $steps] = $this->make(['plan' => [], 'fetch' => ['a', 'b', 'c'], 'apply' => [], 'finalise' => []]);
        $this->newRunner($steps)->begin('r1');

        $guard = 0;
        do {
            // A brand new runner and store every single time.
            $run = $this->newRunner($steps)->step('r1');
        } while (! $run->isFinished() && ++$guard < 100);

        $this->assertSame(Run::DONE, $run->state);
        $this->assertSame(['fetch:a', 'fetch:b', 'fetch:c'], $steps->done);
    }

    public function test_a_failure_records_which_step_failed(): void
    {
        // 🚨 The anti-example is "has been attempted too many times": true,
        // useless, and three layers from the cause.
        [$runner, $steps] = $this->make(['plan' => [], 'fetch' => ['a', 'boom', 'c'], 'apply' => [], 'finalise' => []]);
        $steps->explodeOn = 'boom';
        $runner->begin('r1');

        $guard = 0;
        do {
            $run = $runner->step('r1');
        } while (! $run->isFinished() && ++$guard < 50);

        $this->assertSame(Run::FAILED, $run->state);
        $this->assertSame('fetch → boom', $run->errorStep);
        $this->assertStringContainsString('could not fetch boom', (string) $run->error);
    }

    public function test_stepping_a_finished_run_returns_it_unchanged_rather_than_doing_nothing_quietly(): void
    {
        [$runner, $steps] = $this->make(['plan' => [], 'fetch' => ['a'], 'apply' => [], 'finalise' => []]);
        $runner->begin('r1');

        $guard = 0;
        do {
            $run = $runner->step('r1');
        } while (! $run->isFinished() && ++$guard < 50);

        $before = count($steps->done);
        $again = $runner->step('r1');

        $this->assertSame(Run::DONE, $again->state);
        $this->assertCount($before, $steps->done, 'a finished run must not be re-run');
    }

    public function test_progress_is_reportable_at_every_point(): void
    {
        // What the admin screen binds to. A bar that cannot say where it is, is
        // the spinner problem wearing a different hat.
        [$runner, $steps] = $this->make(['plan' => [], 'fetch' => ['a', 'b', 'c', 'd'], 'apply' => [], 'finalise' => []]);
        $runner->begin('r1');

        $seen = [];
        $guard = 0;
        do {
            $run = $runner->step('r1');

            if ($run->phase === 'fetch' && $run->total() > 0 && ! $run->isFinished()) {
                $seen[] = round($run->fraction(), 2);
                $this->assertNotNull($run->current() ?? ($run->index >= $run->total() ? 'end' : null));
            }
        } while (! $run->isFinished() && ++$guard < 50);

        $this->assertNotEmpty($seen);
        $this->assertSame($seen, array_values(array_unique($seen)), 'progress went backwards or stalled');
    }

    public function test_a_stale_run_is_reported_not_silently_skipped(): void
    {
        // 🚨 the previous Extension Manager refuses to queue while a task looks busy and says
        // nothing, so one dead task blocks everything forever. Here staleness is
        // a fact the caller can act on, never a reason to do nothing.
        [$runner, $steps] = $this->make(['plan' => [], 'fetch' => ['a', 'b'], 'apply' => [], 'finalise' => []]);
        $runner->begin('r1');
        $runner->step('r1');
        $run = $runner->step('r1');

        $this->assertFalse($run->isStale($this->clock));

        $this->clock += 600;

        $this->assertTrue($run->isStale($this->clock), 'a run nobody has advanced for ten minutes is stale');

        // And it can still be advanced — staleness never blocks.
        $next = $runner->step('r1');
        $this->assertGreaterThan($run->index, $next->index);
    }

    public function test_the_state_file_is_never_seen_half_written(): void
    {
        [$runner, $steps] = $this->make(['plan' => [], 'fetch' => ['a', 'b', 'c'], 'apply' => [], 'finalise' => []]);
        $runner->begin('r1');

        $store = new RunStore($this->dir);

        for ($i = 0; $i < 8; $i++) {
            $runner->step('r1');
            // A reader arriving between writes must always get a whole run.
            $this->assertInstanceOf(Run::class, $store->load('r1'));
        }
    }

    // ── helpers ──────────────────────────────────────────────────────────

    /** @return array{0: StepRunner, 1: object} */
    private function make(array $plan): array
    {
        $steps = $this->fakeSteps($plan);

        return [$this->newRunner($steps), $steps];
    }

    /**
     * The guarantee this buys: a finished run means the change is in effect.
     * Before it existed, a step whose work was done but not yet LIVE had to
     * report success, and the run went green while the site was still serving
     * code that did not match its own database.
     */
    public function test_a_step_that_is_not_live_yet_holds_the_run_rather_than_finishing_it(): void
    {
        [$runner, $steps] = $this->make(['finalise' => ['register', 'code cache']]);
        $steps->waitOn = 'code cache';
        $steps->waitFor = 2;
        $runner->begin('r1');

        $guard = 0;
        do {
            $run = $runner->step('r1');
        } while (! $runner->wasWaiting() && ! $run->isFinished() && ++$guard < 50);

        $this->assertTrue($runner->wasWaiting(), 'the runner reports the pause');
        $this->assertFalse($run->isFinished(), 'a waiting run must not report itself finished');
        $this->assertSame(['finalise:register'], $steps->done, 'the waiting item is not marked done');

        $before = $run->index;
        $run = $runner->step('r1');
        $this->assertSame($before, $run->index, 'a second poll does not advance past it either');

        $guard = 0;
        do {
            $run = $runner->step('r1');
        } while (! $run->isFinished() && ++$guard < 50);

        $this->assertTrue($run->isFinished(), 'once it is live the run completes');
        $this->assertContains('finalise:code cache', $steps->done);
    }

    /**
     * 🚨 The log is what the admin screen shows, and a waiting step is polled
     * every couple of seconds by three drivers at once. Appending each time
     * would bury the run's real history under a wall of the same sentence.
     */
    public function test_waiting_does_not_fill_the_log_with_the_same_line(): void
    {
        [$runner, $steps] = $this->make(['finalise' => ['code cache']]);
        $steps->waitOn = 'code cache';
        $steps->waitFor = 20;
        $runner->begin('r1');

        $run = null;

        for ($i = 0; $i < 8; $i++) {
            $run = $runner->step('r1');
        }

        $this->assertSame(1, count(array_filter($run->log, fn ($l) => $l === 'not live yet')));
    }

    private function newRunner(object $steps): StepRunner
    {
        return new StepRunner(new RunStore($this->dir), $steps, fn () => $this->clock);
    }

    private function fakeSteps(array $plan): object
    {
        return new class($plan) implements Steps {
            public array $done = [];
            public ?string $explodeOn = null;

            /** Item that is not finished yet, and how many more calls it needs. */
            public ?string $waitOn = null;
            public int $waitFor = 0;

            public function __construct(private array $plan)
            {
            }

            public function itemsFor(string $phase, Run $run): array
            {
                return $this->plan[$phase] ?? [];
            }

            public function doItem(string $phase, string $item, Run $run): ?string
            {
                if ($item === $this->explodeOn) {
                    throw new \RuntimeException("could not fetch $item");
                }

                if ($item === $this->waitOn && $this->waitFor > 0) {
                    $this->waitFor--;

                    throw new \Flarum\ExtensionManager\Run\NotYet('not live yet');
                }

                $this->done[] = "$phase:$item";

                return "$phase $item";
            }
        };
    }
}
