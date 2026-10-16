<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Apply\Tree;
use PHPUnit\Framework\TestCase;

/**
 * 🚨 Docker's overlay filesystem refuses to rename a directory from the image,
 * so Tree::move falls back to copy-then-delete. That fallback can be killed
 * between its copy and its delete; these pin how it is finished. (EXDEV itself
 * cannot be produced on a single test filesystem; it was proved on
 * Flarum-in-a-box.).
 */
class TreeMoveTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-move-'.bin2hex(random_bytes(4));
        mkdir($this->dir.'/vendor/acme/widget/src', 0775, true);
        file_put_contents($this->dir.'/vendor/acme/widget/src/A.php', 'old');
    }

    protected function tearDown(): void
    {
        Tree::delete($this->dir);
    }

    public function test_a_plain_rename_still_just_renames(): void
    {
        $this->assertTrue(Tree::move($this->dir.'/vendor/acme/widget', $this->dir.'/trash'));
        $this->assertSame('old', file_get_contents($this->dir.'/trash/src/A.php'));
        $this->assertDirectoryDoesNotExist($this->dir.'/vendor/acme/widget');
    }

    public function test_a_copy_killed_after_it_was_whole_is_finished_not_redone(): void
    {
        // The state a kill mid-delete leaves: a whole copy with its marker,
        // and a source with some files already gone.
        mkdir($this->dir.'/trash/src', 0775, true);
        file_put_contents($this->dir.'/trash/src/A.php', 'old');
        touch($this->dir.'/trash/'.Tree::COPIED);
        unlink($this->dir.'/vendor/acme/widget/src/A.php');

        $this->assertTrue(Tree::finishMove($this->dir.'/vendor/acme/widget', $this->dir.'/trash'));
        $this->assertDirectoryDoesNotExist($this->dir.'/vendor/acme/widget');
        $this->assertSame('old', file_get_contents($this->dir.'/trash/src/A.php'));
        $this->assertFileDoesNotExist($this->dir.'/trash/'.Tree::COPIED);
    }

    public function test_without_the_marker_there_is_nothing_to_finish(): void
    {
        mkdir($this->dir.'/trash');

        $this->assertFalse(Tree::finishMove($this->dir.'/vendor/acme/widget', $this->dir.'/trash'));
        $this->assertFileExists($this->dir.'/vendor/acme/widget/src/A.php');
    }
}
