<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Formatter;

use Flarum\Formatter\Formatter;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FormatterJsTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir().'/flarum-formatter-test-'.uniqid();
        mkdir($this->cacheDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*') ?: []);
            rmdir($this->cacheDir);
        }
    }

    private function makeFormatter(?string $polyfillUrl): Formatter
    {
        $formatter = new Formatter(new Repository(new ArrayStore()), $this->cacheDir);
        $formatter->setXsltPolyfillUrlResolver(fn () => $polyfillUrl);

        return $formatter;
    }

    #[Test]
    public function get_js_returns_only_s9e_when_polyfill_url_is_null(): void
    {
        $formatter = $this->makeFormatter(null);

        $output = $formatter->getJs();

        $this->assertStringNotContainsString('xslt-polyfill', $output);
    }

    #[Test]
    public function get_js_prepends_polyfill_loader_when_url_provided(): void
    {
        $formatter = $this->makeFormatter('/assets/xslt-polyfill/xslt-polyfill.min.js');

        $output = $formatter->getJs();

        $this->assertStringContainsString('/assets/xslt-polyfill/xslt-polyfill.min.js', $output);
        $this->assertStringContainsString("createElement('script')", $output);
    }

    #[Test]
    public function get_js_loader_is_short(): void
    {
        $formatter = $this->makeFormatter('/assets/xslt-polyfill/xslt-polyfill.min.js');

        $s9eOnly = $this->makeFormatter(null)->getJs();
        $withLoader = $formatter->getJs();

        $loaderSize = strlen($withLoader) - strlen($s9eOnly);

        // The detector + script-tag injection should be tiny — guard against
        // accidentally re-introducing inline polyfill content.
        $this->assertLessThan(500, $loaderSize, "Polyfill loader is $loaderSize bytes; expected under 500.");
    }

    #[Test]
    public function get_js_loader_escapes_url_against_script_injection(): void
    {
        // The URL gets JSON-encoded so a hostile asset URL can't break out of
        // the JS string context. The s9e bundle is appended after our loader
        // and contains its own escaped sequences — we only assert against the
        // loader prefix.
        $formatter = $this->makeFormatter('/assets/</script><script>alert(1)</script>');
        $s9eOnly = $this->makeFormatter(null)->getJs();

        $output = $formatter->getJs();
        $loaderPrefix = substr($output, 0, strlen($output) - strlen($s9eOnly));

        $this->assertStringNotContainsString('</script>', $loaderPrefix);
        $this->assertStringNotContainsString('<script>alert', $loaderPrefix);
    }

    #[Test]
    public function get_js_loader_does_not_block_when_xslt_works(): void
    {
        // The detector calls `new XSLTProcessor()` and short-circuits via
        // `return` when it succeeds, leaving the s9e bundle untouched.
        $formatter = $this->makeFormatter('/assets/xslt-polyfill/xslt-polyfill.min.js');

        $output = $formatter->getJs();

        $this->assertMatchesRegularExpression('/new XSLTProcessor\(\).*?return/s', $output);
    }
}
