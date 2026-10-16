<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Discover\Cache;
use Flarum\ExtensionManager\Discover\Packagist;
use PHPUnit\Framework\TestCase;

/**
 * 🚨 The rule these protect: an empty query means BROWSE, never "return
 * nothing".
 *
 * The previous Extension Manager shows everything installable the moment you open it, and
 * that is the right behaviour — somebody opening a tab called "Find extensions"
 * is asking what exists, not to guess a search term first. The first version of
 * this refused an empty query and left the tab blank, which made a catalogue
 * behave like a command line and read, reasonably, as broken.
 */
class DiscoverBrowseTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-browse-'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->dir));
    }

    /**
     * @return array{0: Packagist, 1: \ArrayObject<int,string>}
     *
     * An ArrayObject rather than an array, because a by-reference local would be
     * copied on return and the recorded URLs would always come back empty.
     */
    private function packagist(string $body): array
    {
        $urls = new \ArrayObject();

        $p = new Packagist(new Cache($this->dir), function (string $url) use ($urls, $body) {
            $urls->append($url);

            return $body;
        });

        return [$p, $urls];
    }

    private function body(int $count, bool $next = false): string
    {
        $results = [];

        for ($i = 0; $i < $count; $i++) {
            $results[] = ['name' => "acme/pkg$i", 'description' => 'x', 'downloads' => 100 - $i];
        }

        return json_encode(['results' => $results, 'total' => 2306] + ($next ? ['next' => 'https://packagist.org/...'] : []));
    }

    public function test_an_empty_query_asks_packagist_for_everything(): void
    {
        [$p, $urls] = $this->packagist($this->body(12));

        $found = $p->search('');

        $this->assertCount(12, $found['results']);
        $this->assertStringContainsString('type=flarum-extension', $urls[0] ?? '');
        $this->assertStringNotContainsString('&q=', $urls[0] ?? '', 'an empty q would narrow it to nothing');
    }

    public function test_a_query_narrows_it(): void
    {
        [$p, $urls] = $this->packagist($this->body(3));

        $p->search('upload');

        $this->assertStringContainsString('q=upload', $urls[0] ?? '');
    }

    public function test_there_is_more_when_packagist_says_so(): void
    {
        // Browsing 2306 extensions twelve at a time needs to know when to stop
        // offering, and Packagist says so by handing back a next-page URL.
        [$withNext] = $this->packagist($this->body(12, true));
        $this->assertTrue($withNext->search('')['more']);

        [$without] = $this->packagist($this->body(4, false));
        $this->assertFalse($without->search('')['more']);
    }

    public function test_paging_asks_for_the_page_it_was_given(): void
    {
        [$p, $urls] = $this->packagist($this->body(12, true));

        $p->search('', 12, 3);

        $this->assertStringContainsString('page=3', $urls[0] ?? '');
    }

    public function test_a_category_narrows_the_search_the_way_packagist_can(): void
    {
        [$p, $urls] = $this->packagist($this->body(3));

        $p->search('', 12, 1, 'widget');
        $p->search('dark', 12, 1, 'theme');
        $p->search('', 12, 1, 'language');

        $this->assertStringContainsString('&tags[]=widget&tags[]=widgets', $urls[0] ?? '');
        $this->assertStringNotContainsString('&q=', $urls[0] ?? '');
        // Themes are found by the word, alongside whatever was typed.
        $this->assertStringContainsString('q=dark%20theme', $urls[1] ?? '');
        $this->assertStringContainsString('&tags[]=locale&tags[]=language', $urls[2] ?? '');
    }

    public function test_an_unknown_category_never_reaches_the_url(): void
    {
        [$p, $urls] = $this->packagist($this->body(3));

        $p->search('', 12, 1, '&evil=1');

        $this->assertStringNotContainsString('evil', $urls[0] ?? '');
        $this->assertStringNotContainsString('tags', $urls[0] ?? '');
    }

    public function test_an_unreachable_packagist_is_reported_not_silently_empty(): void
    {
        /*
         * 🚨 "Nothing found" and "could not reach Packagist" look identical on
         * screen and mean opposite things: the first ends a search, the second
         * means try again in a minute.
         */
        $p = new Packagist(new Cache($this->dir), fn () => null);
        $found = $p->search('');

        $this->assertSame([], $found['results']);
        $this->assertNotNull($found['error']);
        $this->assertFalse($found['more']);
    }
}
