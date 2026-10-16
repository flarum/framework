<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Apply\Journal;
use Flarum\ExtensionManager\Apply\Rollback;
use Flarum\ExtensionManager\Plan\Change;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * 🚨 The safety case. If these fail, the Extension Manager has no reason to exist.
 *
 * The claim being tested is the one the previous Extension Manager cannot make: kill an
 * update at ANY point and the site is still intact, and can still be put back
 * exactly as it was. Not "usually recoverable with ssh" — recoverable by
 * replaying a file.
 *
 * Each case kills a real subprocess with SIGKILL at a named step. An exception
 * would be a weaker test: it unwinds the stack and runs destructors, which a
 * host killing an overrunning request does not.
 */
class CrashRecoveryTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/extension-manager-'.bin2hex(random_bytes(6));
        mkdir($this->root, 0775, true);
    }

    protected function tearDown(): void
    {
        $this->rmrf($this->root);
    }

    /**
     * Every step an apply passes through. Killing at each one covers the whole
     * sequence: before anything is touched, after the old version is stashed but
     * before the new one lands (the only moment the package is absent), after it
     * lands but before the journal says so, and after the record is complete.
     */
    public static function killPoints(): array
    {
        return [
            'before any filesystem change' => ['journalled'],
            'old version stashed, new one not yet in place' => ['stashed'],
            'new version in place, journal not yet marked done' => ['installed'],
            'change fully recorded' => ['completed'],
        ];
    }

    #[DataProvider('killPoints')]
    public function test_an_apply_killed_at_any_step_rolls_back_to_exactly_the_original_tree(string $killAt): void
    {
        $this->seedVendor(['acme/widget' => 'OLD widget v1', 'acme/gadget' => 'gadget v1']);
        $this->seedStaging(['acme/widget' => 'NEW widget v2']);

        $before = $this->snapshot($this->dir('vendor'));

        $exit = $this->runApply([
            (new Change(Change::REPLACE, 'acme/widget', '1.0.0', '2.0.0'))->toArray(),
        ], $killAt);

        $this->assertNotSame(0, $exit, 'the harness was supposed to be killed, not to finish');

        (new Rollback($this->dir('vendor'), $this->dir('trash'), $this->journal()))->run();

        $this->assertSame(
            $before,
            $this->snapshot($this->dir('vendor')),
            "rollback after a kill at '$killAt' did not restore the original tree"
        );
    }

    #[DataProvider('killPoints')]
    public function test_an_added_package_killed_at_any_step_is_rolled_back_out(string $killAt): void
    {
        $this->seedVendor(['acme/gadget' => 'gadget v1']);
        $this->seedStaging(['acme/newthing' => 'brand new']);

        $before = $this->snapshot($this->dir('vendor'));

        $this->runApply([
            (new Change(Change::ADD, 'acme/newthing', null, '1.0.0'))->toArray(),
        ], $killAt);

        (new Rollback($this->dir('vendor'), $this->dir('trash'), $this->journal()))->run();

        $this->assertSame(
            $before,
            $this->snapshot($this->dir('vendor')),
            "rollback after a kill at '$killAt' left the added package behind"
        );
    }

    public function test_a_completed_apply_actually_changed_the_tree(): void
    {
        // The counterpart to the tests above: prove the thing does its job when
        // it is not interrupted, or they would all pass on a no-op.
        $this->seedVendor(['acme/widget' => 'OLD widget v1']);
        $this->seedStaging(['acme/widget' => 'NEW widget v2']);

        $exit = $this->runApply([
            (new Change(Change::REPLACE, 'acme/widget', '1.0.0', '2.0.0'))->toArray(),
        ], 'never');

        $this->assertSame(0, $exit);
        $this->assertSame('NEW widget v2', $this->read('vendor/acme/widget/file.txt'));
        $this->assertTrue($this->journal()->isComplete());
    }

    public function test_rollback_after_a_finished_apply_returns_the_old_version(): void
    {
        $this->seedVendor(['acme/widget' => 'OLD widget v1']);
        $this->seedStaging(['acme/widget' => 'NEW widget v2']);
        $before = $this->snapshot($this->dir('vendor'));

        $this->runApply([
            (new Change(Change::REPLACE, 'acme/widget', '1.0.0', '2.0.0'))->toArray(),
        ], 'never');

        $this->assertSame('NEW widget v2', $this->read('vendor/acme/widget/file.txt'));

        (new Rollback($this->dir('vendor'), $this->dir('trash'), $this->journal()))->run();

        $this->assertSame($before, $this->snapshot($this->dir('vendor')));
    }

    public function test_a_kill_partway_through_a_multi_package_apply_rolls_back_all_of_it(): void
    {
        // The realistic shape of a core update: several packages, and the
        // process dies in the middle of the run rather than the middle of a step.
        $this->seedVendor([
            'acme/one' => 'one v1',
            'acme/two' => 'two v1',
            'acme/three' => 'three v1',
        ]);
        $this->seedStaging([
            'acme/one' => 'one v2',
            'acme/two' => 'two v2',
            'acme/three' => 'three v2',
        ]);

        $before = $this->snapshot($this->dir('vendor'));

        // Killed at the second package's stash — one change fully done, one
        // half done, one never started.
        $this->runApply([
            (new Change(Change::REPLACE, 'acme/one', '1', '2'))->toArray(),
            (new Change(Change::REPLACE, 'acme/two', '1', '2'))->toArray(),
            (new Change(Change::REPLACE, 'acme/three', '1', '2'))->toArray(),
        ], 'stashed', killOnPackage: 'acme/two');

        (new Rollback($this->dir('vendor'), $this->dir('trash'), $this->journal()))->run();

        $this->assertSame($before, $this->snapshot($this->dir('vendor')));
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private function runApply(array $changes, string $killAt, ?string $killOnPackage = null): int
    {
        $harness = __DIR__.'/../fixtures/crash-apply.php';

        // `exec` so the shell replaces itself with PHP rather than supervising
        // it — otherwise it reports every SIGKILL as "Killed: 9" on the test
        // runner's stderr and buries real failures in noise.
        $cmd = sprintf(
            'exec php %s %s %s %s %s %s %s %s 2>/dev/null',
            escapeshellarg($harness),
            escapeshellarg($this->dir('vendor')),
            escapeshellarg($this->dir('staging')),
            escapeshellarg($this->dir('trash')),
            escapeshellarg($this->dir('journal.jsonl')),
            escapeshellarg(json_encode($changes)),
            escapeshellarg($killAt),
            escapeshellarg((string) $killOnPackage)
        );

        exec($cmd, $out, $exit);

        return $exit;
    }

    private function journal(): Journal
    {
        return new Journal($this->dir('journal.jsonl'));
    }

    private function dir(string $name): string
    {
        return $this->root.'/'.$name;
    }

    /** @param array<string,string> $packages */
    private function seedVendor(array $packages): void
    {
        foreach ($packages as $name => $content) {
            $this->writeTree($this->dir('vendor').'/'.$name, $content);
        }
    }

    /** @param array<string,string> $packages */
    private function seedStaging(array $packages): void
    {
        foreach ($packages as $name => $content) {
            $this->writeTree($this->dir('staging').'/'.$name, $content);
        }
    }

    private function writeTree(string $dir, string $content): void
    {
        mkdir($dir.'/src', 0775, true);
        file_put_contents($dir.'/file.txt', $content);
        file_put_contents($dir.'/src/Deep.php', "<?php // $content");
    }

    private function read(string $relative): string
    {
        return trim((string) @file_get_contents($this->root.'/'.$relative));
    }

    /**
     * Path => content for every file under a directory, sorted.
     *
     * Comparing this before and after is the strongest available statement of
     * "the tree is exactly as it was" — not merely that the right directories
     * exist, but that nothing inside them changed.
     *
     * @return array<string,string>
     */
    private function snapshot(string $dir): array
    {
        if (! is_dir($dir)) {
            return [];
        }

        $files = [];
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($items as $item) {
            if ($item->isFile()) {
                $rel = substr($item->getPathname(), strlen($dir) + 1);
                $files[$rel] = (string) file_get_contents($item->getPathname());
            }
        }

        ksort($files);

        return $files;
    }

    private function rmrf(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }

        @rmdir($dir);
    }
}
