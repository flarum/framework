<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Api\Controller\RollbackController;
use Flarum\ExtensionManager\Exception\RefusedException;
use Flarum\ExtensionManager\Host\StorageDir;
use Flarum\ExtensionManager\Run\Run;
use Flarum\ExtensionManager\Run\RunLock;
use Flarum\ExtensionManager\Run\RunStore;
use Flarum\ExtensionManager\Tests\unit\EnglishTranslator;
use Flarum\ExtensionManager\Work\WorkDir;
use Flarum\Foundation\Paths;
use Flarum\Http\RequestUtil;
use Flarum\User\User;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;

/**
 * One holder at a time for a run, and a lock that cannot be made is said
 * plainly rather than passed off as "busy" or quietly skipped.
 * flarum/framework#5357.
 */
class RunLockTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-lock-'.bin2hex(random_bytes(4));
        mkdir($this->dir, 0775, true);
    }

    protected function tearDown(): void
    {
        @chmod($this->dir.'/locks/run1.lock', 0644);
        exec('rm -rf '.escapeshellarg($this->dir));
    }

    public function test_a_second_holder_is_told_the_run_is_busy(): void
    {
        $lock = new RunLock(EnglishTranslator::make(), $this->dir.'/locks');
        $held = $lock->acquire('run1');

        $this->assertNotNull($held);
        $this->assertNull($lock->acquire('run1'));

        $lock->release($held);
        $this->assertNotNull($lock->acquire('run1'));
    }

    public function test_a_lock_file_owned_by_someone_else_is_an_error_not_busy(): void
    {
        // The command line run as root leaves a lock file the web server cannot
        // open. Reported as "busy", the page waited for a run that was not running.
        if (function_exists('posix_getuid') && posix_getuid() === 0) {
            $this->markTestSkipped('root can open any file');
        }

        mkdir($this->dir.'/locks');
        touch($this->dir.'/locks/run1.lock');
        chmod($this->dir.'/locks/run1.lock', 0);

        $this->expectException(RefusedException::class);
        $this->expectExceptionMessage('run1.lock');
        (new RunLock(EnglishTranslator::make(), $this->dir.'/locks'))->acquire('run1');
    }

    public function test_a_lock_directory_that_cannot_be_made_does_not_turn_locking_off(): void
    {
        file_put_contents($this->dir.'/not-a-dir', '');

        $this->expectException(RefusedException::class);
        (new RunLock(EnglishTranslator::make(), $this->dir.'/not-a-dir/locks'))->acquire('run1');
    }

    public function test_a_rollback_while_a_step_holds_the_run_is_refused_and_changes_nothing(): void
    {
        $storage = $this->dir.'/storage';
        $runs = new RunStore(EnglishTranslator::make(), StorageDir::of($storage).'/runs');
        $runs->save(Run::start('run1', time()));
        $work = new WorkDir(EnglishTranslator::make(), $storage, 'run1');
        @mkdir(dirname($work->journalPath()), 0775, true);
        file_put_contents($work->journalPath(), '');

        $stepLock = new RunLock(EnglishTranslator::make(), StorageDir::of($storage).'/locks');
        $held = $stepLock->acquire('run1');

        $controller = new RollbackController(
            EnglishTranslator::make(),
            $runs,
            new Paths(['base' => $this->dir, 'public' => $this->dir.'/public', 'storage' => $storage, 'vendor' => $this->dir.'/vendor'])
        );

        $admin = $this->createStub(User::class);

        try {
            $controller->handle(RequestUtil::withActor(new ServerRequest(), $admin));
            $this->fail('the rollback ran while a step held the run');
        } catch (RefusedException $e) {
            $this->assertSame(409, $e->getStatus());
        } finally {
            $stepLock->release($held);
        }

        $this->assertSame(Run::RUNNING, $runs->load('run1')->state);
    }
}
