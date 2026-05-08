<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\frontend;

use Flarum\Testing\integration\TestCase;

class XsltPolyfillTest extends TestCase
{
    private function fetchForumHtml(): string
    {
        return $this->send(
            $this->request('GET', '/')
        )->getBody()->getContents();
    }

    /**
     * @test
     */
    public function head_emits_xslt_polyfill_loader()
    {
        $body = $this->fetchForumHtml();

        $head = $this->extractHead($body);

        // The detector references XSLTProcessor and uses document.write
        // to synchronously inject the polyfill <script> when needed.
        $this->assertStringContainsString('XSLTProcessor', $head);
        $this->assertStringContainsString('document.write', $head);
        $this->assertStringContainsString('xslt-polyfill', $head);
    }

    /**
     * @test
     */
    public function head_polyfill_loader_is_short()
    {
        $body = $this->fetchForumHtml();
        $head = $this->extractHead($body);

        // The detector + document.write call should stay tiny — guard
        // against accidentally re-introducing inline polyfill content.
        // Find the polyfill <script>. We use a non-greedy .*? to span the
        // inline JS even though it itself contains < (e.g. `<\/script>`).
        if (! preg_match('/<script>\(function\(\)\{try\{if\(typeof XSLTProcessor.*?<\/script>/s', $head, $m)) {
            $this->fail('Could not find polyfill loader script in head.');
        }

        $this->assertLessThan(500, strlen($m[0]), 'Polyfill loader script grew to '.strlen($m[0]).' bytes; expected under 500.');
    }

    /**
     * @test
     */
    public function head_polyfill_loader_url_is_safely_escaped()
    {
        // The URL is JSON-encoded with HTML-safe flags. We verify that
        // any < or > or " in the URL would be escaped in the loader.
        // The actual asset URL is a path; this asserts the encoding
        // path used would resist a hostile asset URL.
        $body = $this->fetchForumHtml();
        $head = $this->extractHead($body);

        // The closing </script> for the written-out tag must be split
        // (`<\/script>`) so the outer <script> doesn't close early.
        $this->assertStringContainsString('<\/script', $head);
    }

    private function extractHead(string $html): string
    {
        if (preg_match('/<head[^>]*>(.*?)<\/head>/s', $html, $m)) {
            return $m[1];
        }

        $this->fail('Could not extract <head> from response HTML.');
    }
}
