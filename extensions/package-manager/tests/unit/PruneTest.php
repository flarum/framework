<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Apply\Applier;
use Flarum\ExtensionManager\Apply\Journal;
use Flarum\ExtensionManager\Apply\Rollback;
use Flarum\ExtensionManager\Plan\Change;
use Flarum\ExtensionManager\Prune\Contained;
use Flarum\ExtensionManager\Prune\Pruner;
use Flarum\ExtensionManager\Prune\Retention;
use Flarum\ExtensionManager\Run\Run;
use Flarum\ExtensionManager\Run\RunStore;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * 🚨 The rule these protect: a prune can never take a copy a rollback needs.
 *
 * The trash is the rollback. Everything the Extension Manager promises — "nothing is
 * deleted; an interrupted update loses progress, never your site" — rests on
 * the copies in it, and this is the first code that removes any. So every
 * KEEP decision is tested as carefully as every remove, and the deletion itself
 * is tested against the two ways a recursive delete has destroyed work before:
 * following a link out of the tree, and being handed a path outside it.
 *
 * Ages are driven by the Pruner's clock, not by backdating files: a rename sets
 * ctime to now and nothing can set it back, so "two days later" is the clock
 * moving forward, which is also what really happens.
 */
class PruneTest extends TestCase
{
    private const DAY = 86400;

    private string $dir;
    private string $mw;
    private int $now;
    private RunStore $store;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-prune-'.bin2hex(random_bytes(4));
        $this->mw = $this->dir.'/storage/extension-manager';
        mkdir($this->mw.'/trash', 0775, true);
        mkdir($this->mw.'/runs', 0775, true);
        mkdir($this->dir.'/vendor', 0775, true);

        // Two days after everything on disk was made, so the grace period has passed.
        $this->now = time() + 2 * self::DAY;
        $this->store = new RunStore($this->mw.'/runs');
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->dir));
    }

    private function pruner(?int $now = null): Pruner
    {
        $clock = $now ?? $this->now;

        return new Pruner($this->mw, new Retention($this->mw), fn () => $clock);
    }

    /**
     * A run whose journal names the given trash copies, which are created.
     *
     * @param list<string> $packages vendor/name@from
     */
    private function makeRun(string $id, int $daysAgo, array $packages, string $state = Run::DONE): void
    {
        $at = $this->now - $daysAgo * self::DAY;
        $this->store->save(new Run($id, $state, 'finalise', [], 0, [], null, null, $at, $at));

        mkdir($this->mw.'/runs/'.$id, 0775, true);
        file_put_contents($this->mw.'/runs/'.$id.'/composer.lock.before', '{}');
        $journal = new Journal($this->mw.'/runs/'.$id.'/journal.jsonl');

        foreach ($packages as $spec) {
            [$package, $from] = explode('@', $spec);
            $change = new Change(Change::REPLACE, $package, $from, $from.'.1');
            $seq = $journal->begin(['change' => $change->toArray(), 'trash' => $change->trashName()]);
            $journal->complete($seq);
            $this->copy($change->trashName());
        }
    }

    private function copy(string $name): void
    {
        $path = $this->mw.'/trash/'.$name;
        @mkdir($path.'/src', 0775, true);
        file_put_contents($path.'/src/Extension.php', str_repeat('x', 5000));
    }

    /** @return array<string,string> name => reason, for what the plan removes */
    private function removed(array $plan, string $kind = 'trash'): array
    {
        $out = [];

        foreach ($plan['remove'] as $row) {
            if ($row['kind'] === $kind) {
                $out[$row['name']] = $row['reason'];
            }
        }

        ksort($out);

        return $out;
    }

    private function kept(array $plan, string $kind = 'trash'): array
    {
        $out = [];

        foreach ($plan['keep'] as $row) {
            if ($row['kind'] === $kind) {
                $out[$row['name']] = $row['reason'];
            }
        }

        ksort($out);

        return $out;
    }

    // ── what is kept ──────────────────────────────────────────────────────────

    public function test_the_latest_runs_copies_are_kept_whatever_the_settings_say(): void
    {
        (new Retention($this->mw))->save(0, 0);

        $this->makeRun('r-old', 400, ['acme/a@1.0.0']);
        $this->makeRun('r-new', 300, ['acme/b@2.0.0']);   // newest, and a year old

        $plan = $this->pruner()->plan();

        $this->assertArrayHasKey('acme+b@2.0.0', $this->kept($plan), 'the copy Roll back would restore must never be pruned');
        $this->assertSame(['acme+a@1.0.0' => 'expired'], $this->removed($plan));
    }

    public function test_the_most_recent_finished_run_is_kept_behind_an_unfinished_one(): void
    {
        (new Retention($this->mw))->save(0, 0);

        $this->makeRun('r1', 200, ['acme/old@1.0.0']);
        $this->makeRun('r2', 100, ['acme/done@1.0.0']);
        $this->makeRun('r3', 50, ['acme/failed@1.0.0'], Run::FAILED);

        $plan = $this->pruner()->plan();

        $this->assertArrayHasKey('acme+failed@1.0.0', $this->kept($plan), 'the latest run, even failed');
        $this->assertArrayHasKey('acme+done@1.0.0', $this->kept($plan), 'the most recent FINISHED run');
        $this->assertSame(['acme+old@1.0.0' => 'expired'], $this->removed($plan));
    }

    public function test_an_in_progress_run_keeps_its_copies_and_its_directory(): void
    {
        (new Retention($this->mw))->save(0, 0);

        $this->makeRun('r1', 90, ['acme/stalled@1.0.0'], Run::RUNNING);
        $this->makeRun('r2', 80, ['acme/a@1.0.0']);
        $this->makeRun('r3', 70, ['acme/b@1.0.0']);

        // Not the newest by start time, yet still running — a stalled run.
        $plan = $this->pruner()->plan();

        $this->assertArrayHasKey('acme+stalled@1.0.0', $this->kept($plan));
        $this->assertArrayHasKey('r1', $this->kept($plan, 'run'));
    }

    public function test_older_than_the_window_is_removed_and_the_run_summary_stays(): void
    {
        // Defaults: 30 days or the last 5 runs, whichever keeps more.
        foreach (range(1, 7) as $i) {
            $this->makeRun("r$i", 100 - $i, ["acme/p$i@1.0.0"]);
        }

        $plan = $this->pruner()->plan();

        $this->assertSame(['acme+p1@1.0.0' => 'expired', 'acme+p2@1.0.0' => 'expired'], $this->removed($plan));
        $this->assertSame(['r1' => 'expired', 'r2' => 'expired'], $this->removed($plan, 'run'));

        $this->pruner()->prune('test');

        $this->assertDirectoryDoesNotExist($this->mw.'/trash/acme+p1@1.0.0');
        $this->assertDirectoryDoesNotExist($this->mw.'/runs/r1');
        $this->assertFileExists($this->mw.'/runs/r1.json', 'the summary is the record that an update happened');
        $this->assertDirectoryExists($this->mw.'/trash/acme+p3@1.0.0');
        $this->assertSame('r7', $this->store->latest()?->id, 'the screen still opens on the same run');
    }

    public function test_whichever_window_is_more_generous_wins(): void
    {
        // Ten runs, one a day. 3 days or 8 runs: the run count keeps more.
        foreach (range(1, 10) as $i) {
            $this->makeRun("r$i", 11 - $i, ["acme/p$i@1.0.0"]);
        }

        (new Retention($this->mw))->save(3, 8);
        $this->assertSame(['acme+p1@1.0.0', 'acme+p2@1.0.0'], array_keys($this->removed($this->pruner()->plan())));

        // 9 days or 2 runs: the days keep more.
        (new Retention($this->mw))->save(9, 2);
        $this->assertSame(['acme+p1@1.0.0'], array_keys($this->removed($this->pruner()->plan())));
    }

    public function test_a_copy_two_runs_share_is_kept_while_either_needs_it(): void
    {
        (new Retention($this->mw))->save(0, 0);

        // The fbsfb pattern: rolled back, then the same update made again.
        $this->makeRun('r1', 300, ['acme/a@1.0.0'], Run::ROLLBACK);
        $this->makeRun('r2', 200, ['acme/a@1.0.0']);

        $this->assertArrayHasKey('acme+a@1.0.0', $this->kept($this->pruner()->plan()));
    }

    // ── what is removed ───────────────────────────────────────────────────────

    public function test_an_orphan_no_journal_names_is_removed(): void
    {
        $this->makeRun('r1', 1, ['acme/a@1.0.0']);
        $this->copy('acme+mystery@9.9.9');

        $this->assertSame(['acme+mystery@9.9.9' => 'orphan'], $this->removed($this->pruner()->plan()));
    }

    public function test_rolledback_leftovers_go_after_the_grace_period_and_not_before(): void
    {
        $this->makeRun('r1', 1, ['acme/a@1.0.0'], Run::ROLLBACK);
        $this->copy('acme+a@1.0.0.rolledback');
        $this->copy('acme+a@1.0.0.superseded');

        $fresh = $this->pruner(time())->plan();
        $this->assertSame([], $this->removed($fresh), 'nothing that changed in the last day is touched');
        $this->assertSame('grace', $this->kept($fresh)['acme+a@1.0.0.rolledback']);

        $later = $this->pruner()->plan();
        $this->assertSame(
            ['acme+a@1.0.0.rolledback' => 'leftover', 'acme+a@1.0.0.superseded' => 'leftover'],
            $this->removed($later),
        );
        $this->assertArrayHasKey('acme+a@1.0.0', $this->kept($later), 'the copy itself is the latest run\'s');
    }

    public function test_a_fresh_orphan_is_kept_through_the_grace_period(): void
    {
        // What a prune sees mid-apply: a copy that arrived after it read the journals.
        $this->makeRun('r1', 0, []);
        $this->copy('acme+arriving@1.0.0');

        $this->assertSame([], $this->removed($this->pruner(time())->plan()));
    }

    // ── fails closed ──────────────────────────────────────────────────────────

    public function test_an_unreadable_summary_protects_that_runs_directory_and_copies(): void
    {
        (new Retention($this->mw))->save(0, 0);

        $this->makeRun('r1', 300, ['acme/a@1.0.0']);
        $this->makeRun('r2', 200, ['acme/b@1.0.0']);
        $this->makeRun('r3', 100, ['acme/c@1.0.0']);
        file_put_contents($this->mw.'/runs/r1.json', '{ torn');

        $plan = $this->pruner()->plan();

        $this->assertArrayHasKey('r1', $this->kept($plan, 'run'));
        $this->assertArrayHasKey('acme+a@1.0.0', $this->kept($plan));
    }

    public function test_an_unreadable_journal_on_a_protected_run_stops_the_prune(): void
    {
        $this->makeRun('r1', 1, ['acme/a@1.0.0']);
        $this->copy('acme+orphan@1.0.0');

        $journal = $this->mw.'/runs/r1/journal.jsonl';
        chmod($journal, 0000);   // there, but unreadable

        if (is_readable($journal)) {
            $this->markTestSkipped('running as a user who can read anything');
        }

        try {
            $this->pruner()->prune('test');
            $this->fail('a prune that cannot read what a rollback needs must not guess');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Nothing was removed', $e->getMessage());
        }

        $this->assertDirectoryExists($this->mw.'/trash/acme+orphan@1.0.0');
    }

    // ── the deletion itself ───────────────────────────────────────────────────

    public function test_a_symlink_in_the_trash_is_unlinked_and_never_followed(): void
    {
        $checkout = $this->dir.'/my-repo';
        mkdir($checkout.'/src', 0775, true);
        file_put_contents($checkout.'/src/Unpushed.php', '<?php // three days of work');

        // At the top of the trash (a path install somebody stashed by hand)...
        symlink($checkout, $this->mw.'/trash/acme+linked@1.0.0');

        // ...and deep inside an ordinary copy (a vendor/bin style link).
        $this->copy('acme+nested@1.0.0');
        symlink($checkout, $this->mw.'/trash/acme+nested@1.0.0/src/link-to-repo');
        symlink($checkout.'/src/Unpushed.php', $this->mw.'/trash/acme+nested@1.0.0/file-link.php');

        $this->makeRun('r1', 1, ['acme/kept@1.0.0']);

        $summary = $this->pruner()->prune('test');

        $this->assertSame(2, $summary['removed']);
        $this->assertFalse(is_link($this->mw.'/trash/acme+linked@1.0.0'));
        $this->assertDirectoryDoesNotExist($this->mw.'/trash/acme+nested@1.0.0');
        $this->assertFileExists($checkout.'/src/Unpushed.php', 'removing a link must never reach through it');
        $this->assertSame('<?php // three days of work', file_get_contents($checkout.'/src/Unpushed.php'));
    }

    public function test_a_path_outside_the_trash_is_refused(): void
    {
        $outside = $this->dir.'/vendor/acme/widget';
        mkdir($outside, 0775, true);
        file_put_contents($outside.'/keep.php', 'live code');

        $trash = new Contained($this->mw.'/trash');

        foreach (['..', '.', '', '../../../vendor', 'acme/widget', "a\0b"] as $name) {
            try {
                $trash->remove($name);
                $this->fail("'$name' must be refused");
            } catch (RuntimeException) {
                $this->addToAssertionCount(1);
            }
        }

        foreach ([$outside, $this->mw.'/trash/../../../vendor/acme', $this->mw.'/trash/x/../../runs'] as $path) {
            try {
                $trash->removeAt($path);
                $this->fail("$path must be refused");
            } catch (RuntimeException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertFileExists($outside.'/keep.php');
        $this->assertDirectoryExists($this->mw.'/runs');
    }

    public function test_a_trash_that_is_itself_reached_through_a_link_stays_inside_its_target(): void
    {
        // Some hosts symlink storage/ elsewhere. The base is resolved once, and
        // nothing beside the trash's real directory is touched.
        $real = $this->dir.'/elsewhere/trash';
        mkdir($real.'/acme+a@1.0.0', 0775, true);
        mkdir($this->dir.'/elsewhere/sibling', 0775, true);
        symlink($real, $this->dir.'/trash-link');

        $trash = new Contained($this->dir.'/trash-link');
        $trash->remove('acme+a@1.0.0');

        $this->assertDirectoryDoesNotExist($real.'/acme+a@1.0.0');
        $this->assertDirectoryExists($this->dir.'/elsewhere/sibling');
        $this->expectException(RuntimeException::class);
        $trash->removeAt($this->dir.'/elsewhere/sibling');
    }

    // ── the end-to-end promise ────────────────────────────────────────────────

    public function test_the_latest_update_still_rolls_back_after_a_prune(): void
    {
        $vendor = $this->dir.'/vendor';

        // Three real updates of the same package, through the real Applier.
        foreach (['1.0.0' => '1.1.0', '1.1.0' => '1.2.0', '1.2.0' => '1.3.0'] as $from => $to) {
            $id = 'r-'.str_replace('.', '', $to);

            if (! is_dir("$vendor/acme/widget")) {
                mkdir("$vendor/acme/widget", 0775, true);
                file_put_contents("$vendor/acme/widget/version", $from);
            }

            $staging = $this->mw."/runs/$id/staging/acme/widget";
            mkdir($staging, 0775, true);
            file_put_contents("$staging/version", $to);

            $journal = new Journal($this->mw."/runs/$id/journal.jsonl");
            (new Applier($vendor, $this->mw."/runs/$id/staging", $this->mw.'/trash', $journal))
                ->applyOne(new Change(Change::REPLACE, 'acme/widget', $from, $to));

            $at = $this->now - (200 - (int) str_replace('.', '', $to)) * self::DAY;
            $this->store->save(new Run($id, Run::DONE, 'finalise', [], 0, [], null, null, $at, $at));
        }

        (new Retention($this->mw))->save(0, 0);
        $summary = $this->pruner()->prune('test');

        $this->assertSame(
            ['trash:acme+widget@1.0.0', 'trash:acme+widget@1.1.0', 'run:r-110', 'run:r-120'],
            $summary['names']
        );

        $latest = $this->store->latest();
        $this->assertSame('r-130', $latest?->id);

        (new Rollback($vendor, $this->mw.'/trash', new Journal($this->mw.'/runs/r-130/journal.jsonl')))->run();

        $this->assertSame('1.2.0', file_get_contents("$vendor/acme/widget/version"), 'Roll back still puts the previous version back');
    }

    // ── settings and wiring ───────────────────────────────────────────────────

    public function test_a_setting_that_is_not_a_number_falls_back_to_the_default_never_to_zero(): void
    {
        $retention = new Retention($this->mw);

        $retention->save('abc', '-3');
        $this->assertSame(['keepDays' => 30, 'keepRuns' => 5], $retention->settings());

        $retention->save('7', 2);
        $this->assertSame(['keepDays' => 7, 'keepRuns' => 2], $retention->settings());
    }

    public function test_the_prune_is_recorded_for_the_screen(): void
    {
        $this->makeRun('r1', 1, []);
        $this->copy('acme+orphan@1.0.0');

        $this->pruner()->prune('schedule');

        $last = (new Retention($this->mw))->lastPrune();
        $this->assertSame('schedule', $last['trigger']);
        $this->assertSame(1, $last['removed']);
        $this->assertGreaterThan(5000, $last['freed']);
    }

    public function test_the_schedule_passes_no_arguments_and_the_finalise_step_tidies_last(): void
    {
        $extend = (string) file_get_contents(__DIR__.'/../../extend.php');

        // A keyed ['--dry-run' => true] renders as --dry-run='1' and fails nightly, silently.
        $this->assertMatchesRegularExpression(
            '/->schedule\(PruneCommand::class, function \(\$event\): void \{ \$event->dailyAt\(\'[0-9:]+\'\); \}\)/',
            $extend
        );
        $this->assertStringContainsString('->command(PruneCommand::class)', $extend);

        $steps = (string) file_get_contents(__DIR__.'/../../src/Work/ComposerSteps.php');
        $this->assertMatchesRegularExpression("/'check the site again', 'tidy the trash'\\]/", $steps);
        // Whitespace-tolerant: the code style decides alignment, not this test.
        $this->assertMatchesRegularExpression("/'tidy the trash'\\s*=> \\\$this->tidyTrash\\(\\)/", $steps);
    }
}
