<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Formatter;

use Flarum\Formatter\XsltPolyfill;
use Flarum\Testing\unit\TestCase;

class XsltPolyfillTest extends TestCase
{
    /**
     * @test
     */
    public function find_source_locates_the_vendored_polyfill()
    {
        $sourceDir = XsltPolyfill::findSource();

        $this->assertNotNull($sourceDir);
        $this->assertFileExists($sourceDir.'/xslt-polyfill.min.js');
        $this->assertFileExists($sourceDir.'/dist/xslt-wasm.js');
    }

    /**
     * @test
     */
    public function version_returns_the_semver_from_package_json()
    {
        $version = XsltPolyfill::version();

        $this->assertNotNull($version);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', $version);
    }
}
