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
use Flarum\ExtensionManager\Work\ReleaseNotes;
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
        return new ReleaseNotes($this->dir, new AuthTokens(new JsonFile($this->dir.'/auth.json')));
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
}
