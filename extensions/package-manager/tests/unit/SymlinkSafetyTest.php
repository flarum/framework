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
use RuntimeException;

/**
 * 🚨 The rule these protect: a symlink is one thing to remove, never a door.
 *
 * Composer installs a path repository by LINKING vendor/<pkg> at a checkout on
 * disk. That is how every extension developer runs their own work, so it is
 * guaranteed to be present on exactly the machines where the consequences are
 * worst — the ones holding a repository that may not be pushed yet.
 *
 * Both failures here are silent. Following the link deletes somebody's working
 * tree while reporting success; replacing the link leaves their forum running a
 * downloaded copy while they carry on editing a directory nothing reads.
 */
class SymlinkSafetyTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-link-'.bin2hex(random_bytes(4));
        mkdir($this->dir.'/vendor/acme', 0775, true);
        mkdir($this->dir.'/staging/acme', 0775, true);
        mkdir($this->dir.'/trash', 0775, true);
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->dir));
    }

    private function applier(): Applier
    {
        return new Applier(
            $this->dir.'/vendor',
            $this->dir.'/staging',
            $this->dir.'/trash',
            new Journal($this->dir.'/journal.jsonl')
        );
    }

    public function test_a_developers_checkout_is_not_emptied_through_a_stale_stash(): void
    {
        /*
         * The exact route: a path-installed package was stashed by an earlier
         * attempt, so the trash holds a LINK into a real repository. The next
         * attempt finds a stash already there and clears it — and the clearing
         * is where the repository used to die.
         */
        $checkout = $this->dir.'/my-repo';
        mkdir($checkout.'/src', 0775, true);
        file_put_contents($checkout.'/src/Unpushed.php', '<?php // three days of work');

        $change = new Change(Change::REPLACE, 'acme/widget', '1.0.0', '2.0.0');

        symlink($checkout, $this->dir.'/trash/'.$change->trashName());

        // A normal, non-linked install to replace, so the stash actually runs.
        mkdir($this->dir.'/vendor/acme/widget', 0775, true);
        file_put_contents($this->dir.'/vendor/acme/widget/old.php', 'old');
        mkdir($this->dir.'/staging/acme/widget', 0775, true);
        file_put_contents($this->dir.'/staging/acme/widget/new.php', 'new');

        $this->applier()->applyOne($change);

        $this->assertFileExists(
            $checkout.'/src/Unpushed.php',
            'clearing a stale stash must not reach through a symlink into a real checkout'
        );
        $this->assertDirectoryExists($checkout.'/src');
    }

    public function test_the_rollback_does_not_reach_through_a_link_either(): void
    {
        /*
         * 🚨 The applier and the rollback each had their own copy of the delete,
         * and they drifted — the hole was closed in one and left open in the
         * other. There is one copy now (Apply\Tree), and this is here so the
         * second caller cannot quietly regrow its own.
         */
        $checkout = $this->dir.'/my-repo';
        mkdir($checkout.'/src', 0775, true);
        file_put_contents($checkout.'/src/Unpushed.php', '<?php // three days of work');

        $change = new Change(Change::REPLACE, 'acme/widget', '1.0.0', '2.0.0');

        mkdir($this->dir.'/vendor/acme/widget', 0775, true);
        file_put_contents($this->dir.'/vendor/acme/widget/old.php', 'old');
        mkdir($this->dir.'/staging/acme/widget', 0775, true);
        file_put_contents($this->dir.'/staging/acme/widget/new.php', 'new');

        $this->applier()->applyOne($change);

        // A leftover from an earlier rollback, sitting exactly where the next one
        // clears before it moves the live copy aside.
        symlink($checkout, $this->dir.'/trash/'.$change->trashName().'.rolledback');

        (new Rollback(
            $this->dir.'/vendor',
            $this->dir.'/trash',
            new Journal($this->dir.'/journal.jsonl')
        ))->run();

        $this->assertFileExists($checkout.'/src/Unpushed.php');
    }

    public function test_a_path_installed_package_is_refused_rather_than_swapped(): void
    {
        $checkout = $this->dir.'/my-repo';
        mkdir($checkout, 0775, true);
        file_put_contents($checkout.'/composer.json', '{}');

        symlink($checkout, $this->dir.'/vendor/acme/widget');

        mkdir($this->dir.'/staging/acme/widget', 0775, true);
        file_put_contents($this->dir.'/staging/acme/widget/new.php', 'new');

        $change = new Change(Change::REPLACE, 'acme/widget', '1.0.0', '2.0.0');

        try {
            $this->applier()->applyOne($change);
            $this->fail('a path install should be refused');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('local path', $e->getMessage());
            $this->assertStringContainsString('git', $e->getMessage(), 'the refusal should say what to do instead');
        }

        $this->assertTrue(is_link($this->dir.'/vendor/acme/widget'), 'the link stays exactly where it was');
        $this->assertFileExists($checkout.'/composer.json');

        // 🚨 And nothing was written down. A refusal that leaves a half-open
        // journal entry makes the NEXT run refuse too, for a reason that has
        // nothing to do with what is wrong.
        $this->assertFileDoesNotExist($this->dir.'/journal.jsonl');
    }
}
