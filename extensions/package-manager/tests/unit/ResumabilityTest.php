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
use PHPUnit\Framework\TestCase;

/**
 * 🚨 The claim this whole design exists to support: an update finishes on a host
 * that keeps killing it.
 *
 * Every other test here kills a process at a point chosen to be interesting.
 * These kill it at points chosen at random, over and over, until the run
 * completes — which is a far better model of a real shared host, where the cut
 * comes wherever the clock happens to be rather than somewhere thoughtful.
 *
 * What is asserted is deliberately not "each item ran exactly once". The driver
 * saves progress AFTER doing the work, so a kill in between repeats that item on
 * resume. That is a chosen trade: repeating work is recoverable, silently
 * skipping it is not. These tests characterise the repeat rather than pretend it
 * cannot happen.
 */
class ResumabilityTest extends TestCase
{
    private string $dir;
    private string $log;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-resume-'.bin2hex(random_bytes(6));
        mkdir($this->dir, 0775, true);
        $this->log = $this->dir.'/done.log';
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir.'/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->dir);
    }

    public function test_a_run_killed_repeatedly_at_random_points_still_completes_every_item(): void
    {
        $plan = [
            'plan' => ['resolve'],
            'fetch' => ['pkg-a', 'pkg-b', 'pkg-c', 'pkg-d', 'pkg-e'],
            'apply' => ['pkg-a', 'pkg-b', 'pkg-c', 'pkg-d', 'pkg-e'],
            'finalise' => ['autoload', 'migrate', 'assets'],
        ];

        $expected = [];
        foreach ($plan as $phase => $items) {
            foreach ($items as $item) {
                $expected[] = "$phase:$item";
            }
        }

        $processes = 0;

        // Each process gets a small, random budget of steps and is then killed.
        // Nothing carries over except what is on disk.
        while ($processes < 200) {
            $processes++;
            $this->spawn($plan, random_int(1, 3));

            $run = (new RunStore($this->dir))->load('r1');

            if ($run !== null && $run->isFinished()) {
                break;
            }
        }

        $run = (new RunStore($this->dir))->load('r1');

        $this->assertNotNull($run);
        $this->assertSame(Run::DONE, $run->state, "gave up after $processes processes");

        $done = $this->completedItems();

        foreach ($expected as $item) {
            $this->assertContains($item, $done, "$item was never done");
        }

        $this->assertSame(
            $expected,
            array_values(array_unique($done)),
            'items ran out of order, or something ran that was not planned'
        );
    }

    public function test_repeated_work_is_bounded_rather_than_unlimited(): void
    {
        // A repeat per kill is the trade. A run that redid everything each time
        // would never finish on a host that kills often, so the cost has to be
        // proportional to the number of interruptions, not to the work.
        $plan = ['plan' => [], 'fetch' => ['a', 'b', 'c', 'd'], 'apply' => [], 'finalise' => []];

        $processes = 0;
        while ($processes < 100) {
            $processes++;
            $this->spawn($plan, 1);   // the harshest case: one step per process

            $run = (new RunStore($this->dir))->load('r1');

            if ($run !== null && $run->isFinished()) {
                break;
            }
        }

        $done = $this->completedItems();

        $this->assertSame(Run::DONE, (new RunStore($this->dir))->load('r1')?->state);
        $this->assertLessThanOrEqual(
            count($plan['fetch']) * 2,
            count($done),
            'work was repeated more than once per item'
        );
    }

    public function test_a_run_that_finishes_is_not_restarted_by_further_polling(): void
    {
        // The admin screen keeps polling after completion; that must be inert.
        $plan = ['plan' => [], 'fetch' => ['a', 'b'], 'apply' => [], 'finalise' => []];

        for ($i = 0; $i < 30; $i++) {
            $this->spawn($plan, 5);

            if ((new RunStore($this->dir))->load('r1')?->isFinished()) {
                break;
            }
        }

        $after = count($this->completedItems());

        for ($i = 0; $i < 5; $i++) {
            $this->spawn($plan, 5);
        }

        $this->assertCount($after, $this->completedItems(), 'polling a finished run did more work');
    }

    public function test_a_kill_inside_the_work_then_record_gap_repeats_that_item_and_still_completes(): void
    {
        /*
         * 🚨 The gap idempotency exists for, and the one the other tests here do
         * NOT reach: killing after a step returns lands after the save, so the
         * item is never repeated. This kills between doing the work and
         * recording it, which is the only moment where a resumed run redoes
         * something.
         *
         * The assertion is that the item repeats — not that it does not. Pretending
         * otherwise would be the bug: phase 2's applier is written so a repeat is
         * a no-op precisely because this is unavoidable.
         */
        $plan = ['plan' => [], 'fetch' => ['a', 'b', 'c'], 'apply' => [], 'finalise' => []];

        $this->spawn($plan, 20, killInside: 'b');

        $afterKill = $this->completedItems();
        $this->assertSame(['fetch:a', 'fetch:b'], $afterKill, 'the kill did not land where intended');

        $this->spawn($plan, 20);

        $this->assertSame(
            ['fetch:a', 'fetch:b', 'fetch:b', 'fetch:c'],
            $this->completedItems(),
            'the interrupted item should be redone once, and the run carry on'
        );

        $this->assertSame(Run::DONE, (new RunStore($this->dir))->load('r1')?->state);
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private function spawn(array $plan, int $budget, ?string $killInside = null): void
    {
        $cmd = sprintf(
            'exec php %s %s %s %s %s %d',
            escapeshellarg(__DIR__.'/../fixtures/step-run.php'),
            escapeshellarg($this->dir),
            escapeshellarg('r1'),
            escapeshellarg(json_encode($plan)),
            escapeshellarg($this->log),
            $budget
        ).' '.escapeshellarg((string) $killInside).' 2>/dev/null';

        exec($cmd);
    }

    /** @return list<string> */
    private function completedItems(): array
    {
        if (! is_file($this->log)) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode("\n", (string) file_get_contents($this->log)))
        ));
    }
}
