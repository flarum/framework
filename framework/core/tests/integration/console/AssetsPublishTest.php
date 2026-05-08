<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\console;

use Flarum\Testing\integration\ConsoleTestCase;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\Test;

class AssetsPublishTest extends ConsoleTestCase
{
    private function getAssetsDisk(): Filesystem
    {
        return $this->app()->getContainer()->make(Factory::class)->disk('flarum-assets');
    }

    #[Test]
    public function publish_command_copies_xslt_polyfill_when_present_in_node_modules(): void
    {
        $disk = $this->getAssetsDisk();
        $disk->delete('xslt-polyfill/xslt-polyfill.min.js');
        $disk->delete('xslt-polyfill/dist/xslt-wasm.js');

        $this->runCommand(['command' => 'assets:publish']);

        // The monorepo's hoisted node_modules has the polyfill installed via
        // the framework/core/js/package.json dependency, so publish should
        // emit both files into the assets disk preserving the dist/ layout.
        $this->assertTrue(
            $disk->exists('xslt-polyfill/xslt-polyfill.min.js'),
            'xslt-polyfill.min.js was not published into the flarum-assets disk.'
        );
        $this->assertTrue(
            $disk->exists('xslt-polyfill/dist/xslt-wasm.js'),
            'dist/xslt-wasm.js was not published into the flarum-assets disk.'
        );
    }

    #[Test]
    public function published_polyfill_matches_source(): void
    {
        $disk = $this->getAssetsDisk();

        $this->runCommand(['command' => 'assets:publish']);

        $publishedSize = $disk->size('xslt-polyfill/xslt-polyfill.min.js');

        // The source file lives in the monorepo's hoisted node_modules.
        $sourcePath = __DIR__.'/../../../../../node_modules/xslt-polyfill/xslt-polyfill.min.js';
        $this->assertFileExists($sourcePath, 'xslt-polyfill not installed in node_modules; cannot verify the publish.');

        $this->assertEquals(filesize($sourcePath), $publishedSize, 'Published polyfill size differs from source.');
    }
}
