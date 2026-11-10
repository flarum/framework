<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Api\Controller\StartController;
use Flarum\ExtensionManager\Exception\RefusedException;
use Flarum\ExtensionManager\Run\Run;
use Flarum\ExtensionManager\Run\RunStore;
use Flarum\ExtensionManager\Run\StartLock;
use Flarum\ExtensionManager\Run\StartLockUnwritable;
use Flarum\ExtensionManager\Run\StepRunner;
use Flarum\ExtensionManager\Run\Steps;
use Flarum\ExtensionManager\Tests\unit\EnglishTranslator;
use Flarum\Foundation\Paths;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Two starts at once make one run, never two.
 *
 * The check for an unfinished run and the creation of the new one were two
 * separate steps, so two requests arriving together both passed the check and
 * both runs went on to rewrite the manifests and vendor/, each with a journal
 * that knew nothing of the other's changes.
 */
class StartLockTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-start-'.bin2hex(random_bytes(6));
        mkdir($this->dir.'/extension-manager', 0775, true);
    }

    protected function tearDown(): void
    {
        @chmod($this->dir.'/extension-manager', 0775);
        $this->delete($this->dir);
    }

    public function test_a_start_while_another_start_holds_the_lock_is_refused_and_creates_nothing(): void
    {
        $other = new StartLock($this->dir.'/extension-manager/locks');
        $this->assertTrue($other->acquire());

        try {
            $this->begin();
            $this->fail('A second start went ahead while the first still held the lock.');
        } catch (RefusedException $e) {
            $this->assertSame(409, $e->getStatus());
            $this->assertSame('run_in_progress', $e->getType());
        } finally {
            $other->release();
        }

        $this->assertNull($this->store()->latest(), 'Nothing may be started.');
        $this->assertSame([], glob($this->dir.'/extension-manager/runs/*/requested.json') ?: [], 'No work directory may be made either.');
    }

    public function test_once_the_lock_is_free_a_start_goes_ahead_and_lets_go_of_it(): void
    {
        $run = $this->begin();

        $this->assertSame(Run::RUNNING, $run->state);
        $this->assertSame($run->id, $this->store()->latest()?->id);

        // Released on the way out, or every later start would be refused.
        $after = new StartLock($this->dir.'/extension-manager/locks');
        $this->assertTrue($after->acquire());
        $after->release();
    }

    /** Inside the lock, an unfinished run is still in the way, and the lock is still let go. */
    public function test_an_unfinished_run_is_still_refused_and_the_lock_is_still_released(): void
    {
        $this->store()->save(Run::start('r-going', time()));

        try {
            $this->begin();
            $this->fail('A run was started on top of an unfinished one.');
        } catch (RefusedException $e) {
            $this->assertSame(409, $e->getStatus());
        }

        $this->assertSame('r-going', $this->store()->latest()?->id);

        $after = new StartLock($this->dir.'/extension-manager/locks');
        $this->assertTrue($after->acquire(), 'A refusal must not leave the lock held.');
        $after->release();
    }

    /**
     * A lock that cannot be made refuses, rather than starting unguarded: that
     * would be exactly the race this exists to stop. But it says so, rather
     * than passing for a lock somebody else holds, which goes away by itself.
     */
    public function test_a_lock_directory_that_cannot_be_made_is_told_apart_from_a_held_lock(): void
    {
        $this->unwritable();

        try {
            (new StartLock($this->dir.'/extension-manager/locks'))->acquire();
            $this->fail('A lock that could not be made was reported as held.');
        } catch (StartLockUnwritable $e) {
            $this->assertSame($this->dir.'/extension-manager/locks', $e->path);
        }
    }

    /** The screen gets its own error, naming the folder, and no run is started. */
    public function test_the_screen_is_told_the_lock_folder_cannot_be_written(): void
    {
        $this->unwritable();

        try {
            $this->begin();
            $this->fail('A start went ahead without its lock.');
        } catch (RefusedException $e) {
            $this->assertSame(500, $e->getStatus());
            $this->assertSame('start_lock_unwritable', $e->getType());
            $this->assertStringContainsString($this->dir.'/extension-manager/locks', $e->getMessage());
            $this->assertStringNotContainsString('already in progress', $e->getMessage());
        }

        chmod($this->dir.'/extension-manager', 0775);
        $this->assertNull($this->store()->latest(), 'Nothing may be started.');
    }

    public function test_the_same_holder_can_ask_twice_and_release_twice(): void
    {
        $lock = new StartLock($this->dir.'/extension-manager/locks');

        $this->assertTrue($lock->acquire());
        $this->assertTrue($lock->acquire());
        $lock->release();
        $lock->release();

        $this->assertTrue((new StartLock($this->dir.'/extension-manager/locks'))->acquire());
    }

    /**
     * The real race: several processes start at the same moment, as a double
     * click, two tabs, or the screen and the command line would.
     */
    public function test_starts_racing_each_other_make_exactly_one_run(): void
    {
        $go = microtime(true) + 1.0;
        $handles = [];

        for ($i = 0; $i < 8; $i++) {
            $handles[] = popen($this->raceCommand($go), 'r');
        }

        $outcomes = [];

        foreach ($handles as $handle) {
            $outcomes[] = trim((string) stream_get_contents($handle));
            pclose($handle);
        }

        $runs = glob($this->dir.'/extension-manager/runs/*.json') ?: [];

        $this->assertCount(1, array_keys($outcomes, 'started', true), 'Outcomes: '.implode(', ', $outcomes));
        $this->assertCount(7, array_keys($outcomes, 'refused', true), 'Outcomes: '.implode(', ', $outcomes));
        $this->assertCount(1, array_filter($runs, fn (string $f) => basename($f) !== 'history.json'));
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function unwritable(): void
    {
        if (function_exists('posix_getuid') && posix_getuid() === 0) {
            $this->markTestSkipped('root can write anywhere');
        }

        chmod($this->dir.'/extension-manager', 0555);
    }

    private function store(): RunStore
    {
        return new RunStore(EnglishTranslator::make(), $this->dir.'/extension-manager/runs');
    }

    private function begin(): Run
    {
        return self::beginIn($this->dir);
    }

    /** StartController::begin(), built without a container. Also used by the race's child processes. */
    public static function beginIn(string $dir): Run
    {
        $store = new RunStore(EnglishTranslator::make(), $dir.'/extension-manager/runs');
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
            'translator' => EnglishTranslator::make(),
            'runs' => $store,
            'runner' => new StepRunner(EnglishTranslator::make(), $store, $steps, fn (): int => time()),
            'paths' => new Paths(['base' => $dir, 'public' => $dir, 'storage' => $dir, 'vendor' => $dir]),
        ] as $prop => $value) {
            $reflection->getProperty($prop)->setValue($controller, $value);
        }

        return $reflection->getMethod('begin')->invoke($controller, ['a/thing'], 'update', []);
    }

    private function raceCommand(float $go): string
    {
        $autoload = dirname(__DIR__, 4).'/vendor/autoload.php';
        $code = sprintf(
            'require %s; require %s; require %s; while (microtime(true) < %F) { usleep(200); } '
            .'try { \Flarum\ExtensionManager\Tests\Unit\StartLockTest::beginIn(%s); echo "started"; } '
            .'catch (\Flarum\ExtensionManager\Exception\RefusedException $e) { echo "refused"; }',
            var_export($autoload, true),
            var_export(__DIR__.'/EnglishTranslator.php', true),
            var_export(__FILE__, true),
            $go,
            var_export($this->dir, true)
        );

        return escapeshellarg(PHP_BINARY).' -d memory_limit=-1 -r '.escapeshellarg($code).' 2>&1';
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
