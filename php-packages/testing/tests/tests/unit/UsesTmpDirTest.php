<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Testing\Tests\unit;

use FilesystemIterator;
use Flarum\Testing\integration\UsesTmpDir;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class UsesTmpDirTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        // Somewhere that does not exist yet: setupTmpDir() keeps whatever it
        // finds, so only a fresh directory shows what it creates.
        $this->dir = sys_get_temp_dir().'/flarum-testing-'.bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        if (is_dir($this->dir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->dir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }

            rmdir($this->dir);
        }

        parent::tearDown();
    }

    #[Test]
    public function the_directory_translations_are_cached_in_is_created()
    {
        $dir = $this->dir;

        $harness = new class($dir) {
            use UsesTmpDir;

            public function __construct(private string $dir)
            {
            }

            public function tmpDir()
            {
                return $this->dir;
            }
        };

        $harness->setupTmpDir();

        // The translator writes its catalogues here without creating it, so
        // a test that translated anything warned about a missing directory.
        $this->assertDirectoryExists($dir.'/storage/locale');
    }
}
