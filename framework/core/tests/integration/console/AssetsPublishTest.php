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
    public function publish_command_copies_xslt_polyfill(): void
    {
        $disk = $this->getAssetsDisk();
        $disk->delete('xslt-polyfill/xslt-polyfill.min.js');
        $disk->delete('xslt-polyfill/dist/xslt-wasm.js');

        $this->runCommand(['command' => 'assets:publish']);

        // The polyfill is vendored in framework/core/js/dist/xslt-polyfill/
        // and ships with flarum/core, so publish should always emit both
        // files into the assets disk preserving the dist/ layout.
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

        $sourcePath = __DIR__.'/../../../js/dist/xslt-polyfill/xslt-polyfill.min.js';
        $this->assertFileExists($sourcePath);

        $this->assertEquals(filesize($sourcePath), $publishedSize, 'Published polyfill size differs from source.');
    }
}
