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
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * A search page (`/?q=…`, `/posts?q=…`) preloads the search's results, which
 * the frontend shows as they are. "0" is a valid search term, not an empty
 * query, so it must not preload the unfiltered list.
 */
class SearchPreloadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => 'Apples and pears', 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Apples and pears</p></t>', 'number' => 1],
            ],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>> The preloaded resources of the given type.
     */
    protected function preloaded(string $path, string $type, array $query): array
    {
        $response = $this->send(
            $this->request('GET', $path)->withQueryParams($query)
        );

        $this->assertEquals(200, $response->getStatusCode());

        preg_match('/<script id="flarum-json-payload" type="application\/json">(.+?)<\/script>/s', (string) $response->getBody(), $matches);

        $payload = json_decode(html_entity_decode($matches[1]), true);

        return array_values(array_filter(
            $payload['apiDocument']['data'] ?? [],
            fn (array $resource) => $resource['type'] === $type
        ));
    }

    #[Test]
    public function the_index_without_a_search_preloads_the_list()
    {
        $this->assertCount(1, $this->preloaded('/', 'discussions', []));
    }

    #[Test]
    public function a_search_for_zero_on_the_index_preloads_its_results()
    {
        $this->assertCount(0, $this->preloaded('/', 'discussions', ['q' => '0']));
    }

    #[Test]
    public function the_posts_page_without_a_search_preloads_the_list()
    {
        $this->assertCount(1, $this->preloaded('/posts', 'posts', []));
    }

    #[Test]
    public function a_search_for_zero_on_the_posts_page_preloads_its_results()
    {
        $this->assertCount(0, $this->preloaded('/posts', 'posts', ['q' => '0']));
    }
}
