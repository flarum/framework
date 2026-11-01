<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Api\Controller\StartController;
use Flarum\ExtensionManager\Apply\Applier;
use Flarum\ExtensionManager\Apply\Journal;
use Flarum\ExtensionManager\Run\Run;
use Flarum\ExtensionManager\Run\RunStore;
use Flarum\ExtensionManager\Run\StepRunner;
use Flarum\ExtensionManager\Run\Steps;
use Flarum\ExtensionManager\Tests\unit\EnglishTranslator;
use Flarum\ExtensionManager\Work\ComposerRunner;
use Flarum\ExtensionManager\Work\ComposerSteps;
use Flarum\ExtensionManager\Work\Fetcher;
use Flarum\ExtensionManager\Work\SavedManifests;
use Flarum\Foundation\Paths;
use Flarum\Locale\TranslatorInterface;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

/**
 * A run that fails before its new composer.lock is meant to stay puts the old
 * one back.
 *
 * The plan phase lets Composer rewrite the lock, and only the permission check
 * used to put it back. A failed download or apply left the new lock in place;
 * the next run then saved it as its own starting point, planned nothing and
 * finished "nothing to update" while vendor/ still had the old code, and the
 * update check read the same lock and agreed.
 */
class FailedRunManifestsTest extends TestCase
{
    private string $dir;
    private string $work;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-manifests-'.bin2hex(random_bytes(6));
        $this->work = $this->dir.'/storage/extension-manager/runs/r1';
        mkdir($this->work.'/staging', 0775, true);
        mkdir($this->dir.'/vendor', 0775, true);

        file_put_contents($this->work.'/composer.json.before', 'OLD-JSON');
        file_put_contents($this->work.'/composer.lock.before', 'OLD-LOCK');
        file_put_contents($this->dir.'/composer.json', 'NEW-JSON');
        file_put_contents($this->dir.'/composer.lock', 'NEW-LOCK');
        file_put_contents($this->work.'/plan.json', json_encode([
            'changes' => [['op' => 'replace', 'package' => 'acme/widget', 'from' => '1.0.0', 'to' => '2.0.0']],
            'sources' => [],
        ]));
    }

    protected function tearDown(): void
    {
        $this->delete($this->dir);
    }

    // ── a failure the run catches puts them back itself ──────────────────────

    public function test_a_failed_download_puts_the_old_manifests_back(): void
    {
        // No archive for the package in the plan: the fetch fails.
        $this->expectFailure(fn () => $this->steps()->doItem('fetch', 'acme/widget', Run::start('r1', time())));

        $this->assertSame('OLD-LOCK', file_get_contents($this->dir.'/composer.lock'));
        $this->assertSame('OLD-JSON', file_get_contents($this->dir.'/composer.json'));
    }

    public function test_a_failed_apply_puts_the_old_manifests_back(): void
    {
        $this->expectFailure(fn () => $this->steps()->doItem('apply', 'not/in-the-plan', Run::start('r1', time())));

        $this->assertSame('OLD-LOCK', file_get_contents($this->dir.'/composer.lock'));
        $this->assertSame('OLD-JSON', file_get_contents($this->dir.'/composer.json'));
    }

    /**
     * A resolve Composer refused, after a pin was raised for it. Composer
     * undoes its own edit when it fails, but not ours: the raised pin stayed in
     * composer.json.
     */
    public function test_a_failed_resolve_takes_a_raised_pin_back_out(): void
    {
        unlink($this->work.'/composer.json.before');
        unlink($this->work.'/composer.lock.before');
        file_put_contents($this->dir.'/composer.json', json_encode(['require' => ['acme/widget' => '1.0.0']]));
        file_put_contents($this->dir.'/composer.lock', json_encode(['packages' => []]));
        $original = (string) file_get_contents($this->dir.'/composer.json');

        $steps = $this->steps(repin: ['acme/widget' => '2.0.0'], requested: ['acme/widget']);

        $this->expectFailure(fn () => $steps->doItem('plan', 'work out what changes', Run::start('r1', time())));

        $this->assertSame($original, file_get_contents($this->dir.'/composer.json'), 'The raised pin must not outlive the failed resolve.');
    }

    /**
     * A plan Composer worked out but that could not be saved: on a full disk a
     * short write read back as no plan at all, and the run applied nothing and
     * finished "done". Now it fails the plan phase, which puts the manifests
     * back.
     */
    public function test_a_plan_that_cannot_be_saved_fails_planning_and_puts_the_manifests_back(): void
    {
        unlink($this->work.'/plan.json');
        file_put_contents($this->dir.'/composer.json', json_encode(['require' => ['acme/widget' => '^1.0']]));
        file_put_contents($this->dir.'/composer.lock', json_encode(['packages' => [['name' => 'acme/widget', 'version' => '1.0.0']]]));
        unlink($this->work.'/composer.json.before');
        unlink($this->work.'/composer.lock.before');
        $old = (string) file_get_contents($this->dir.'/composer.lock');

        // Composer "succeeds" and writes a new lock; then the plan cannot be written.
        $steps = $this->steps(requested: ['acme/widget'], resolves: json_encode(['packages' => [['name' => 'acme/widget', 'version' => '2.0.0']]]));
        // A directory where the file goes: the write fails as a full disk's would.
        mkdir($this->work.'/plan.json');

        try {
            $steps->doItem('plan', 'work out what changes', Run::start('r1', time()));
            $this->fail('A plan that could not be saved was reported as planned.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Could not save the plan', $e->getMessage());
        }

        $this->assertSame($old, file_get_contents($this->dir.'/composer.lock'));
    }

    /**
     * From Finishing up on, every package is in place and Composer's record
     * is built from the new lock: that lock is meant to stay, and Finishing up
     * is retried or rolled back, never re-planned.
     */
    public function test_a_failure_while_finishing_up_leaves_the_new_lock_in_place(): void
    {
        $this->expectFailure(fn () => $this->steps()->doItem('finalise', 'register', Run::start('r1', time())));

        $this->assertSame('NEW-LOCK', file_get_contents($this->dir.'/composer.lock'));
        $this->assertSame('NEW-JSON', file_get_contents($this->dir.'/composer.json'));
    }

    public function test_a_step_that_succeeds_changes_nothing(): void
    {
        $this->steps()->doItem('plan', 'check the site', Run::start('r1', time()));

        $this->assertSame('NEW-LOCK', file_get_contents($this->dir.'/composer.lock'));
    }

    public function test_putting_back_keeps_the_live_files_own_mode(): void
    {
        chmod($this->dir.'/composer.lock', 0640);

        $this->assertSame(['composer.lock', 'composer.json'], SavedManifests::putBack($this->work, $this->dir));
        $this->assertSame(0640, fileperms($this->dir.'/composer.lock') & 0777);
        $this->assertSame([], glob($this->dir.'/composer.lock.extension-manager-*') ?: [], 'No temporary file may be left behind.');
    }

    public function test_a_run_with_nothing_saved_has_nothing_to_put_back(): void
    {
        unlink($this->work.'/composer.json.before');
        unlink($this->work.'/composer.lock.before');

        $this->assertSame([], SavedManifests::putBack($this->work, $this->dir));
        $this->assertSame('NEW-LOCK', file_get_contents($this->dir.'/composer.lock'));
    }

    // ── one it could not catch is put back before the next run ───────────────

    public function test_a_run_killed_while_downloading_is_put_back_before_the_next_one(): void
    {
        $this->assertSame(['composer.lock', 'composer.json'], SavedManifests::afterFailedRun($this->failedIn('fetch'), $this->work, $this->dir));
        $this->assertSame('OLD-LOCK', file_get_contents($this->dir.'/composer.lock'));
    }

    public function test_a_run_that_failed_while_finishing_up_keeps_its_lock(): void
    {
        $this->assertSame([], SavedManifests::afterFailedRun($this->failedIn('finalise'), $this->work, $this->dir));
        $this->assertSame('NEW-LOCK', file_get_contents($this->dir.'/composer.lock'));
    }

    /** Finished, rolled back or still going: none of those owes anything. */
    public function test_only_a_failed_run_is_put_back(): void
    {
        $started = Run::start('r1', time() + 60);

        foreach ([$started, $started->finished(time() + 60, 'done'), $started->rolledBack(time() + 60, 'rolled back'), null] as $run) {
            $this->assertSame([], SavedManifests::afterFailedRun($run, $this->work, $this->dir));
        }

        $this->assertSame('NEW-LOCK', file_get_contents($this->dir.'/composer.lock'));
    }

    /**
     * A lock written after the run stopped was written by somebody else, most
     * likely `composer update` by hand, and is theirs to keep.
     */
    public function test_a_lock_changed_since_the_run_failed_is_left_alone(): void
    {
        $run = $this->failedIn('apply', time() - 600);
        touch($this->dir.'/composer.lock', time());

        $this->assertSame([], SavedManifests::afterFailedRun($run, $this->work, $this->dir));
        $this->assertSame('NEW-LOCK', file_get_contents($this->dir.'/composer.lock'));
    }

    /** And the screen's start does the same as the command line's. */
    public function test_the_screen_puts_it_back_before_starting_the_next_run(): void
    {
        $store = new RunStore(EnglishTranslator::make(), $this->dir.'/storage/extension-manager/runs');
        $store->save($this->failedIn('fetch'));

        $this->beginIn($this->dir);

        $this->assertSame('OLD-LOCK', file_get_contents($this->dir.'/composer.lock'));
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function failedIn(string $phase, ?int $at = null): Run
    {
        $at ??= time() + 60;

        return Run::start('r1', $at - 60)->enteredPhase($phase, $at - 30)->failed('it stopped', $phase, $at, 'failed');
    }

    private function expectFailure(callable $step): void
    {
        try {
            $step();
            $this->fail('The step was expected to fail.');
        } catch (RuntimeException|\Error $e) {
            $this->assertNotSame('The step was expected to fail.', $e->getMessage());
        }
    }

    /**
     * @param array<string,string> $repin
     * @param list<string> $requested
     */
    private function steps(array $repin = [], array $requested = [], ?string $resolves = null): ComposerSteps
    {
        $translator = EnglishTranslator::make();
        $journal = new Journal($translator, $this->work.'/journal.jsonl');

        /*
         * Can always spawn, and nothing here reaches Composer for real: every
         * command fails, unless $resolves is given, when it succeeds and
         * writes that as the new lock.
         */
        $composer = new class($translator, $this->dir, $resolves) extends ComposerRunner {
            public function __construct(TranslatorInterface $translator, string $installPath, private ?string $resolves)
            {
                parent::__construct($translator, $installPath);
            }

            public function canSpawn(): bool
            {
                return true;
            }

            public function inProcess(): bool
            {
                return false;
            }

            public function run(array $args, int $timeout = 600): array
            {
                if ($this->resolves !== null) {
                    file_put_contents($this->installPath.'/composer.lock', $this->resolves);

                    return ['code' => 0, 'output' => ''];
                }

                return ['code' => 1, 'output' => 'Your requirements could not be resolved.'];
            }
        };

        return new ComposerSteps(
            $translator,
            $this->dir,
            $this->work,
            $composer,
            new Fetcher($translator, new Client(), $this->work.'/staging'),
            new Applier($translator, $this->dir.'/vendor', $this->work.'/staging', $this->dir.'/storage/extension-manager/trash', $journal),
            $journal,
            new Client(),
            $requested,
            'update',
            repin: $repin,
        );
    }

    private function beginIn(string $base): void
    {
        $translator = EnglishTranslator::make();
        $store = new RunStore($translator, $base.'/storage/extension-manager/runs');
        $steps = new class implements Steps {
            public function itemsFor(string $phase, Run $run): array
            {
                return [];
            }

            public function doItem(string $phase, string $item, Run $run): ?string
            {
                return null;
            }
        };

        $reflection = new ReflectionClass(StartController::class);
        $controller = $reflection->newInstanceWithoutConstructor();

        foreach ([
            'translator' => $translator,
            'runs' => $store,
            'runner' => new StepRunner($translator, $store, $steps, fn (): int => time()),
            'paths' => new Paths(['base' => $base, 'public' => $base, 'storage' => $base.'/storage', 'vendor' => $base.'/vendor']),
        ] as $prop => $value) {
            $reflection->getProperty($prop)->setValue($controller, $value);
        }

        $reflection->getMethod('begin')->invoke($controller, ['acme/widget'], 'update', []);
    }

    private function delete(string $path): void
    {
        if (is_dir($path) && ! is_link($path)) {
            foreach (scandir($path) ?: [] as $entry) {
                if ($entry !== '.' && $entry !== '..') {
                    $this->delete($path.'/'.$entry);
                }
            }
            @rmdir($path);
        } else {
            @unlink($path);
        }
    }
}
