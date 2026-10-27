<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Config\AuthTokens;
use Flarum\ExtensionManager\Config\JsonFile;
use Flarum\ExtensionManager\Tests\unit\EnglishTranslator;
use Flarum\ExtensionManager\Work\ReleaseNotes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReleaseNotesTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-notes-'.bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir.'/*') ?: []);
        @rmdir($this->dir);
    }

    private function notes(): ReleaseNotes
    {
        return new ReleaseNotes($this->dir, new AuthTokens(EnglishTranslator::make(), new JsonFile(EnglishTranslator::make(), $this->dir.'/auth.json')), new \GuzzleHttp\Client());
    }

    private function update(): array
    {
        return ['from' => 'v1.2.0', 'to' => 'v1.5.0', 'notes' => 'https://github.com/acme/widget/releases/tag/v1.5.0'];
    }

    public function test_every_release_between_installed_and_offered_newest_first(): void
    {
        $asked = [];
        $get = function (string $url) use (&$asked) {
            $asked[] = $url;

            return json_encode([
                ['tag_name' => 'v1.6.0', 'name' => 'Too new', 'body_html' => '<p>no</p>'],
                ['tag_name' => 'v1.5.0', 'name' => '', 'body_html' => '<p>five</p>', 'html_url' => 'https://github.com/acme/widget/releases/tag/v1.5.0'],
                ['tag_name' => 'v1.4.0', 'draft' => true, 'body_html' => '<p>draft</p>'],
                ['tag_name' => 'v1.3.0', 'name' => 'Three', 'body_html' => '<p>three</p>'],
                ['tag_name' => 'v1.2.0', 'name' => 'Installed', 'body_html' => '<p>no</p>'],
            ]);
        };

        $result = $this->notes()->between($this->update(), $get);

        $this->assertSame(['https://api.github.com/repos/acme/widget/releases?per_page=100'], $asked);
        $this->assertSame(['v1.5.0', 'v1.3.0'], array_column($result['releases'], 'tag'));
        $this->assertSame('v1.5.0', $result['releases'][0]['name'], 'an unnamed release is called by its tag');
        $this->assertSame('https://github.com/acme/widget/releases/tag/v1.5.0', $result['releases'][0]['url']);

        // Cached: a second look makes no request.
        $this->notes()->between($this->update(), function () {
            $this->fail('asked GitHub again inside the cache window');
        });
    }

    public function test_only_a_github_release_page_is_ever_asked_about(): void
    {
        $never = fn () => $this->fail('made a request');

        $this->assertNull($this->notes()->between(['notes' => 'https://evil.example/acme/widget/releases/tag/1'] + $this->update(), $never));
        $this->assertNull($this->notes()->between(['from' => '1', 'to' => '2'], $never));
    }

    public function test_a_failed_request_is_not_cached(): void
    {
        $this->assertSame([], $this->notes()->between($this->update(), fn () => '{"message":"API rate limit exceeded"}')['releases']);

        $result = $this->notes()->between($this->update(), fn () => json_encode([['tag_name' => 'v1.5.0', 'body_html' => '<p>ok</p>']]));
        $this->assertCount(1, $result['releases']);
    }

    public function test_release_html_is_cut_down_to_safe_markup(): void
    {
        $html = ReleaseNotes::clean(
            '<h2 id="x" onclick="steal()">Fixes</h2><script>steal()</script><ul><li><a href="javascript:steal()">bad</a>'
            .' <a href="https://github.com/a/b/pull/1" class="issue-link">#1</a></li></ul>'
            .'<img src="https://tracker.example/p.gif"><span style="color:red">kept text</span><iframe src="x"></iframe><!-- hidden -->'
        );

        $this->assertStringNotContainsString('script', $html);
        $this->assertStringNotContainsString('steal', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('iframe', $html);
        $this->assertStringNotContainsString('hidden', $html);
        $this->assertStringNotContainsString('style', $html);
        $this->assertStringContainsString('<h2>Fixes</h2>', $html);
        $this->assertStringContainsString('<a>bad</a>', $html);
        $this->assertStringContainsString('<a href="https://github.com/a/b/pull/1" target="_blank" rel="noopener noreferrer nofollow">#1</a>', $html);
        $this->assertStringContainsString('kept text', $html);
    }

    /**
     * Hostile release notes: nothing in the output may run, load or restyle
     * anything once the browser parses it for m.trust().
     */
    #[DataProvider('hostile')]
    public function test_hostile_html_comes_out_inert(string $html): void
    {
        $out = ReleaseNotes::clean($html);

        $this->assertStringNotContainsString('<img', $out);

        // Text is escaped, so every "<" left starts a tag, and each must have exactly this shape.
        preg_match_all('/<[^>]*>?/', $out, $tags);

        foreach ($tags[0] as $tag) {
            $this->assertMatchesRegularExpression('#^</?(p|br|hr|h[1-6]|ul|ol|li|a|strong|b|em|i|code|pre|blockquote|del|table|thead|tbody|tr|th|td|details|summary)>$|^<a href="[^"]*" target="_blank" rel="noopener noreferrer nofollow">$#', $tag);
        }

        foreach ($this->hrefs($out) as $href) {
            $this->assertMatchesRegularExpression('#^https?://[^\s"\'<>]+$#', $href);
        }
    }

    public static function hostile(): array
    {
        $img = '<img src=x onerror=alert(1)>';

        return [
            'script' => ['<script>alert(1)</script>'],
            'event handler' => ['<p onclick="alert(1)">x</p><details open ontoggle=alert(1)><summary>s</summary></details>'],
            'javascript href' => ['<a href="javascript:alert(1)">j</a><a href=" javascript:alert(1)">j</a><a href="JaVaScRiPt:alert(1)">j</a>'],
            'entity-split scheme' => ['<a href="java&#x09;script:alert(1)">j</a><a href="&#106;avascript:alert(1)">j</a>'],
            'data href' => ['<a href="data:text/html,<script>alert(1)</script>">d</a>'],
            'protocol-relative href' => ['<a href="//evil.test/">p</a>'],
            'attribute breakout' => ['<a href="https://x.test/&quot; onmouseover=&quot;alert(1)">q</a><a href=\'https://x.test/" onmouseover="alert(1)\'>q</a>'],
            'svg' => ['<svg><script>alert(1)</script></svg><svg/onload=alert(1)><svg><style>'.$img.'</style></svg>'],
            'math' => ['<math><mtext><table><mglyph><style>'.$img.'</style></mglyph></table></mtext></math>'],
            'style' => ['<style>body{background:url(javascript:alert(1))}</style><p style="color:red">s</p>'],
            'img srcset' => ['<img srcset="x 1x, javascript:alert(1) 2x"><picture><source srcset=x onerror=alert(1)></picture>'],
            'xmp' => ['<xmp>'.$img.'</xmp>'],
            'noembed' => ['<noembed>'.$img.'</noembed>'],
            'noframes' => ['<noframes>'.$img.'</noframes>'],
            'plaintext' => ['<plaintext>'.$img],
            'noscript' => ['<noscript><p title="</noscript>'.$img.'"></noscript>'],
            'title and textarea' => ['<title>'.$img.'</title><textarea>'.$img.'</textarea>'],
            'template' => ['<template>'.$img.'</template>'],
            'iframe srcdoc' => ['<iframe srcdoc="<script>alert(1)</script>"></iframe>'],
            'comments' => ['<!--><img src=x onerror=alert(1)>--><!-- --!>'.$img.'-->'],
            'cdata and processing instruction' => ['<![CDATA['.$img.']]><?php echo 1; ?>'.$img],
            'nested script' => ['<<script>script>alert(1)<</script>/script><scr<script>ipt>alert(1)</scr</script>ipt>'],
            'escaped markup stays text' => ['&lt;script&gt;alert(1)&lt;/script&gt;<p>&#60;img src=x onerror=alert(1)&#62;</p>'],
            'malformed nesting' => ['<p><b><i>nested</p></b>malformed<i>'.$img],
            'closing the wrapper' => ['</body></html>'.$img.'<body onload=alert(1)>'],
            'forms and embeds' => ['<form action="javascript:alert(1)"><button formaction="javascript:alert(1)">b</button></form><object data="javascript:alert(1)"></object><embed src="javascript:alert(1)">'],
            'head elements' => ['<base href="javascript:alert(1)//"><meta http-equiv="refresh" content="0;url=javascript:alert(1)"><link rel=stylesheet href=https://x.test/x.css>'],
            'clobbering attributes' => ['<a href="https://ok.test" id="clobber" name="clobber" target="_self">ok</a>'],
            'custom elements' => ['<x onclick=alert(1)>c</x><g-emoji onclick="alert(1)">e</g-emoji><marquee onstart=alert(1)>m</marquee>'],
        ];
    }

    public function test_raw_text_elements_cannot_smuggle_markup_out_as_text(): void
    {
        // libxml keeps these elements' text unescaped wherever it is moved to.
        $this->assertSame('', ReleaseNotes::clean('<xmp><img src=x onerror=alert(1)></xmp>'));
        $this->assertSame('<p>&lt;b&gt;</p>', ReleaseNotes::clean('<p>&lt;b&gt;</p>'));
    }

    public function test_ordinary_release_notes_keep_their_shape(): void
    {
        $this->assertSame(
            '<h2>Fixes</h2><a></a><ul><li>A &amp; B <code>x &lt; y</code> by <a href="https://github.com/a?b=1&amp;c=2" target="_blank" rel="noopener noreferrer nofollow">@a</a></li></ul><p>line<br>next</p><hr>',
            ReleaseNotes::clean('<div class="markdown-heading"><h2 class="heading-element">Fixes</h2><a id="fixes" class="anchor" href="#fixes"><svg class="octicon"><path d="M0"></path></svg></a></div><ul><li>A &amp; B <code>x &lt; y</code> by <a href="https://github.com/a?b=1&amp;c=2" class="user-mention">@a</a></li></ul><p>line<br/>next</p><hr/>')
        );
    }

    public function test_a_release_link_that_is_not_github_falls_back_to_the_checked_page(): void
    {
        $result = $this->notes()->between($this->update(), fn () => json_encode([
            ['tag_name' => 'v1.5.0', 'body_html' => '', 'html_url' => 'javascript:alert(1)'],
            ['tag_name' => 'v1.4.0', 'body_html' => '', 'html_url' => 'https://evil.test/'],
        ]));

        $this->assertSame([$this->update()['notes'], $this->update()['notes']], array_column($result['releases'], 'url'));
    }

    /** @return list<string> */
    private function hrefs(string $html): array
    {
        preg_match_all('/href="([^"]*)"/', $html, $m);

        return array_map(fn ($h) => html_entity_decode($h, ENT_QUOTES | ENT_HTML5), $m[1]);
    }
}
