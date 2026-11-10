<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Formatter;

use Flarum\Formatter\XsltPolyfill;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class XsltPolyfillTest extends TestCase
{
    #[Test]
    public function find_source_locates_the_vendored_polyfill(): void
    {
        $sourceDir = XsltPolyfill::findSource();

        $this->assertNotNull($sourceDir);
        $this->assertFileExists($sourceDir.'/xslt-polyfill.min.js');
        $this->assertFileExists($sourceDir.'/dist/xslt-wasm.js');
    }

    #[Test]
    public function version_returns_the_semver_from_package_json(): void
    {
        $version = XsltPolyfill::version();

        $this->assertNotNull($version);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', $version);
    }
}
