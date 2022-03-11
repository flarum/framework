<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Tests\integration;

use FilesystemIterator;
use Flarum\PackageManager\Composer\ComposerAdapter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

trait RefreshComposerSetup
{
    public function tearDown(): void
    {
        $composerSetup = new SetupComposer();
        @unlink($this->tmpDir().'/composer.lock');

        $this->deleteDummyExtensions();

        $composerSetup->run();

        $this->composer('install');

        parent::tearDown();
    }

    private function deleteDummyExtensions(): void
    {
        $dir = $this->tmpDir().'/packages';

        $it = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($dir);
    }

    protected function forgetComposerApp(): void
    {
        $this->app()->getContainer()->instance(ComposerAdapter::class, null);
    }
}
