<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\forum;

use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class DiscussionChunkPreloadTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Test Discussion', 'user_id' => 1, 'first_post_id' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>can i haz potat?</p></t>'],
            ],
        ]);
    }

    private function page(string $path): string
    {
        $response = $this->send($this->request('GET', $path));

        $this->assertEquals(200, $response->getStatusCode());

        return (string) $response->getBody();
    }

    /**
     * The address the frontend loads a chunk from (see ExportRegistry's
     * versionedChunkUrl), worked out from what the page hands it.
     */
    private function chunkUrl(string $html, string $path): string
    {
        preg_match('/"jsChunksBaseUrl":("[^"]*")/', $html, $base);
        preg_match('/<script id="flarum-rev-manifest" type="application\/json">(.*?)<\/script>/s', $html, $manifest);

        $file = "core/$path.js";
        $revision = json_decode($manifest[1], true)["js/$file"] ?? null;

        return json_decode($base[1]).'/'.$file.($revision ? "?v=$revision" : '');
    }

    #[Test]
    public function a_discussion_page_preloads_the_chunks_it_shows_its_posts_with()
    {
        $html = $this->page('/d/1');

        foreach (['forum/components/PostStream', 'forum/components/PostStreamScrubber'] as $chunk) {
            $this->assertStringContainsString('<link rel="preload" href="'.e($this->chunkUrl($html, $chunk)).'" as="script">', $html);
        }
    }

    #[Test]
    public function other_pages_do_not_preload_them()
    {
        $this->assertDoesNotMatchRegularExpression('/<link rel="preload" href="[^"]*PostStream/', $this->page('/'));
    }
}
