<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Flarum only publishes a lazily loaded chunk when its folder is registered
 * with Extend\Frontend::jsDirectory(). The release notes window was built as a
 * chunk and never registered, so "Learn more" 404'd on every forum (ClaudiusH).
 *
 * Read from the source rather than js/dist, which the framework does not commit.
 */
class ChunksPublishedTest extends TestCase
{
    public function test_every_frontend_that_loads_code_on_demand_registers_its_chunks(): void
    {
        $root = dirname(__DIR__, 2);
        $extend = (string) file_get_contents($root.'/extend.php');
        $lazy = [];

        foreach (glob($root.'/js/src/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));

            foreach ($files as $file) {
                if (preg_match('/\.(ts|tsx|js)$/', $file->getFilename()) && preg_match('/\bimport\(/', (string) file_get_contents($file->getPathname()))) {
                    $lazy[] = basename($dir);
                    break;
                }
            }
        }

        $this->assertContains('admin', $lazy, 'The admin loads the release notes window on demand.');

        foreach ($lazy as $frontend) {
            $this->assertStringContainsString(
                "->jsDirectory(__DIR__.'/js/dist/$frontend')",
                $extend,
                "js/src/$frontend loads code on demand, but extend.php does not register js/dist/$frontend, so Flarum never publishes its chunks."
            );
        }
    }
}
