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
 * 🚨 On a forum with a queue, two drivers WILL run at once — the admin page
 * polling and a worker picking up the job — and without a lock both read the
 * same index, both do the same item, and a package is applied twice.
 *
 * These start several real processes against one run at the same time.
 */
class ConcurrencyTest extends TestCase
{
    private string $dir;
    private string $log;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-conc-'.bin2hex(random_bytes(6));
        mkdir($this->dir, 0775, true);
        $this->log = $this->dir.'/done.log';
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir.'/{,locks/}*', GLOB_BRACE) ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->dir.'/locks');
        @rmdir($this->dir);
    }

    public function test_drivers_racing_each_other_never_do_the_same_item_twice(): void
    {
        $plan = [
            'plan' => [], 'fetch' => ['a', 'b', 'c', 'd', 'e'],
            'apply' => ['a', 'b', 'c', 'd', 'e'], 'finalise' => [],
        ];

        // Six processes started at once, each trying to drive the same run —
        // roughly what a polling admin page plus a queue worker plus an
        // impatient second browser tab looks like.
        for ($round = 0; $round < 6; $round++) {
            $pids = [];

            for ($i = 0; $i < 6; $i++) {
                $pids[] = popen($this->cmd($plan, 4), 'r');
            }

            foreach ($pids as $h) {
                pclose($h);
            }

            if ((new RunStore($this->dir))->load('r1')?->isFinished()) {
                break;
            }
        }

        $done = $this->completed();

        $this->assertSame(Run::DONE, (new RunStore($this->dir))->load('r1')?->state);

        $this->assertSame(
            array_values(array_unique($done)),
            $done,
            'an item was done more than once — the lock did not hold'
        );

        foreach (['fetch:a', 'fetch:e', 'apply:a', 'apply:e'] as $expected) {
            $this->assertContains($expected, $done);
        }
    }

    public function test_a_driver_that_cannot_get_the_lock_leaves_the_run_alone(): void
    {
        // The important half of "non-blocking": a second driver must not queue up
        // behind the lock and pile requests on a host that is already working.
        mkdir($this->dir.'/locks', 0775, true);
        $held = fopen($this->dir.'/locks/r1.lock', 'c');
        flock($held, LOCK_EX);

        $plan = ['plan' => [], 'fetch' => ['a', 'b'], 'apply' => [], 'finalise' => []];

        $started = microtime(true);
        exec($this->cmd($plan, 3));
        $elapsed = microtime(true) - $started;

        flock($held, LOCK_UN);
        fclose($held);

        $this->assertSame([], $this->completed(), 'it did work while another driver held the lock');
        $this->assertLessThan(5, $elapsed, 'it blocked waiting for the lock instead of returning');
    }

    private function cmd(array $plan, int $budget): string
    {
        return sprintf(
            'exec php %s %s %s %s %s %d 2>/dev/null',
            escapeshellarg(__DIR__.'/../fixtures/step-run.php'),
            escapeshellarg($this->dir),
            escapeshellarg('r1'),
            escapeshellarg(json_encode($plan)),
            escapeshellarg($this->log),
            $budget
        );
    }

    /** @return list<string> */
    private function completed(): array
    {
        if (! is_file($this->log)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode("\n", (string) file_get_contents($this->log)))));
    }
}
