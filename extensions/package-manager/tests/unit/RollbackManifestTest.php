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
use PHPUnit\Framework\TestCase;

/**
 * 🚨 The rule these protect: a rollback puts back the site's RECORD of itself,
 * not only its files.
 *
 * The plan phase runs `composer update --no-install`, which rewrites
 * composer.lock the moment it succeeds. A rollback that moves the directories
 * back and stops there leaves a lock claiming versions that are no longer on
 * disk — and nothing looks wrong. The damage lands at the NEXT update, when
 * Composer diffs against a lock describing work that was undone and plans around
 * packages it believes are already installed.
 */
class RollbackManifestTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-manifest-'.bin2hex(random_bytes(4));
        mkdir($this->dir.'/install/vendor/acme/widget', 0775, true);
        mkdir($this->dir.'/staging/acme/widget', 0775, true);
        mkdir($this->dir.'/work', 0775, true);
        mkdir($this->dir.'/trash', 0775, true);

        file_put_contents($this->dir.'/install/vendor/acme/widget/old.php', 'old');
        file_put_contents($this->dir.'/staging/acme/widget/new.php', 'new');
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->dir));
    }

    private function rollback(): Rollback
    {
        return new Rollback(
            $this->dir.'/install/vendor',
            $this->dir.'/trash',
            new Journal($this->dir.'/work/journal.jsonl'),
            $this->dir.'/install',
            $this->dir.'/work'
        );
    }

    private function applyOne(): void
    {
        (new Applier(
            $this->dir.'/install/vendor',
            $this->dir.'/staging',
            $this->dir.'/trash',
            new Journal($this->dir.'/work/journal.jsonl')
        ))->applyOne(new Change(Change::REPLACE, 'acme/widget', '1.0.0', '2.0.0'));
    }

    public function test_the_lock_goes_back_with_the_files(): void
    {
        file_put_contents($this->dir.'/work/composer.lock.before', '{"packages":[{"name":"acme/widget","version":"1.0.0"}]}');
        file_put_contents($this->dir.'/install/composer.lock', '{"packages":[{"name":"acme/widget","version":"2.0.0"}]}');

        $this->applyOne();
        $undone = $this->rollback()->run();

        $this->assertStringContainsString(
            '"version":"1.0.0"',
            file_get_contents($this->dir.'/install/composer.lock')
        );
        $this->assertContains('restored composer.lock', $undone, 'and it says so, rather than doing it quietly');
        $this->assertFileExists($this->dir.'/install/vendor/acme/widget/old.php');
    }

    public function test_composer_json_goes_back_too_when_an_install_changed_it(): void
    {
        // `composer require` edits composer.json as well as the lock, so an
        // install that is rolled back must not leave the package still required.
        file_put_contents($this->dir.'/work/composer.lock.before', '{"packages":[]}');
        file_put_contents($this->dir.'/work/composer.json.before', '{"require":{}}');
        file_put_contents($this->dir.'/install/composer.lock', '{"packages":[{"name":"acme/widget"}]}');
        file_put_contents($this->dir.'/install/composer.json', '{"require":{"acme/widget":"^2.0"}}');

        $this->applyOne();
        $this->rollback()->run();

        $this->assertSame('{"require":{}}', file_get_contents($this->dir.'/install/composer.json'));
    }

    public function test_a_run_that_never_touched_composer_json_leaves_it_alone(): void
    {
        /*
         * An update saves only the lock, so composer.json.before is absent — its
         * absence is the normal case and must not be read as "restore nothing"
         * or as an error.
         */
        file_put_contents($this->dir.'/work/composer.lock.before', '{"packages":[]}');
        file_put_contents($this->dir.'/install/composer.json', '{"require":{"acme/widget":"^1.0"}}');

        $this->applyOne();
        $undone = $this->rollback()->run();

        $this->assertSame('{"require":{"acme/widget":"^1.0"}}', file_get_contents($this->dir.'/install/composer.json'));
        $this->assertNotContains('restored composer.json', $undone);
    }

    public function test_an_undone_install_leaves_no_empty_vendor_directory(): void
    {
        // "Put everything back the way it was" should not leave an empty
        // vendor/acme/ behind as evidence that it did not quite.
        mkdir($this->dir.'/staging/newco/thing', 0775, true);
        file_put_contents($this->dir.'/staging/newco/thing/it.php', 'new');
        file_put_contents($this->dir.'/work/composer.lock.before', '{"packages":[]}');

        (new Applier(
            $this->dir.'/install/vendor',
            $this->dir.'/staging',
            $this->dir.'/trash',
            new Journal($this->dir.'/work/journal.jsonl')
        ))->applyOne(new Change(Change::ADD, 'newco/thing', null, '1.0.0'));

        $this->assertDirectoryExists($this->dir.'/install/vendor/newco/thing');

        $this->rollback()->run();

        $this->assertDirectoryDoesNotExist($this->dir.'/install/vendor/newco/thing');
        $this->assertDirectoryDoesNotExist($this->dir.'/install/vendor/newco');
        $this->assertDirectoryExists($this->dir.'/install/vendor', 'and never above it');
    }

    public function test_a_run_that_failed_before_moving_any_file_is_still_undone(): void
    {
        /*
         * 🚨 The case that reported "nothing to undo" while leaving a site
         * broken. Composer rewrites composer.json and composer.lock as soon as
         * the plan succeeds — before any file moves, so before the journal has
         * an entry. A failure right after that leaves the manifests describing a
         * site that does not exist, and an empty journal is not permission to
         * ignore it.
         *
         * Found for real: `composer remove --no-install` updated both files and
         * then exited non-zero.
         */
        file_put_contents($this->dir.'/work/composer.lock.before', '{"packages":[{"name":"acme/widget"}]}');
        file_put_contents($this->dir.'/work/composer.json.before', '{"require":{"acme/widget":"^1.0"}}');
        file_put_contents($this->dir.'/install/composer.lock', '{"packages":[]}');
        file_put_contents($this->dir.'/install/composer.json', '{"require":{}}');

        // No apply at all — the journal was never written to.
        $undone = $this->rollback()->run();

        $this->assertSame('{"require":{"acme/widget":"^1.0"}}', file_get_contents($this->dir.'/install/composer.json'));
        $this->assertStringContainsString('acme/widget', file_get_contents($this->dir.'/install/composer.lock'));
        $this->assertContains('restored composer.lock', $undone);
    }

    public function test_an_uninstall_leaves_no_empty_vendor_directory_either(): void
    {
        mkdir($this->dir.'/install/vendor/lonely/pkg', 0775, true);
        file_put_contents($this->dir.'/install/vendor/lonely/pkg/it.php', 'x');
        file_put_contents($this->dir.'/work/composer.lock.before', '{"packages":[]}');

        (new Applier(
            $this->dir.'/install/vendor',
            $this->dir.'/staging',
            $this->dir.'/trash',
            new Journal($this->dir.'/work/journal.jsonl')
        ))->applyOne(new Change(Change::REMOVE, 'lonely/pkg', '1.0.0', null));

        $this->assertDirectoryDoesNotExist($this->dir.'/install/vendor/lonely');
        $this->assertDirectoryExists($this->dir.'/install/vendor');
        $this->assertDirectoryExists($this->dir.'/trash/lonely+pkg@1.0.0', 'and the files are kept, not deleted');
    }

    public function test_restoring_twice_is_harmless(): void
    {
        // The journal makes every step repeatable, and this one is no exception:
        // a rollback interrupted after the files and before the lock is resumed
        // by simply running it again.
        file_put_contents($this->dir.'/work/composer.lock.before', '{"packages":[{"version":"1.0.0"}]}');
        file_put_contents($this->dir.'/install/composer.lock', '{"packages":[{"version":"2.0.0"}]}');

        $this->applyOne();
        $this->rollback()->run();
        $this->rollback()->run();

        $this->assertStringContainsString('1.0.0', file_get_contents($this->dir.'/install/composer.lock'));
    }
}
