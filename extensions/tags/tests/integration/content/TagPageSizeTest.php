<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration\content;

use Carbon\Carbon;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\DiscussionResource;
use Flarum\Extend;
use Flarum\Discussion\Discussion;
use Flarum\Tags\Tag;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * A tag's page is the discussion list filtered to one tag, so it has to page
 * the way the discussion list does: the page size is whatever the discussions
 * index endpoint is configured with, not a number of its own.
 */
class TagPageSizeTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->extend(
            (new Extend\ApiResource(DiscussionResource::class))
                ->endpoint(Endpoint\Index::class, fn (Endpoint\Index $endpoint) => $endpoint->limit(2))
        );

        $discussions = [];
        $links = [];

        foreach (range(1, 5) as $id) {
            $date = Carbon::parse('2020-01-01')->addDays($id)->toDateTimeString();

            $discussions[] = [
                'id' => $id,
                'title' => "Discussion $id",
                'created_at' => $date,
                'last_posted_at' => $date,
                'user_id' => 1,
                'comment_count' => 1,
                'is_private' => 0,
            ];
            $links[] = ['discussion_id' => $id, 'tag_id' => 1];
        }

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Tag::class => [
                ['id' => 1, 'name' => 'Plain', 'slug' => 'plain', 'position' => 0],
            ],
            Discussion::class => $discussions,
            'discussion_tag' => $links,
        ]);
    }

    /**
     * @return array{titles: string[], limit: int, offset: int}
     */
    protected function page(int $page): array
    {
        $response = $this->send(
            $this->request('GET', '/t/plain')->withQueryParams($page > 1 ? ['page' => $page] : [])
        );

        $this->assertEquals(200, $response->getStatusCode());

        preg_match('/<script id="flarum-json-payload" type="application\/json">(.+?)<\/script>/s', (string) $response->getBody(), $matches);

        $document = json_decode(html_entity_decode($matches[1]), true)['apiDocument'];

        return [
            'titles' => array_values(array_map(
                fn (array $d) => $d['attributes']['title'],
                array_filter($document['data'] ?? [], fn (array $d) => $d['type'] === 'discussions')
            )),
            'limit' => $document['meta']['page']['limit'],
            'offset' => $document['meta']['page']['offset'],
        ];
    }

    #[Test]
    public function the_first_page_uses_the_page_size_of_the_discussions_endpoint()
    {
        $page = $this->page(1);

        $this->assertEquals(2, $page['limit']);
        $this->assertEquals(0, $page['offset']);
        $this->assertEquals(['Discussion 5', 'Discussion 4'], $page['titles']);
    }

    #[Test]
    public function later_pages_continue_from_the_end_of_the_previous_one()
    {
        $second = $this->page(2);
        $third = $this->page(3);

        $this->assertEquals(2, $second['offset']);
        $this->assertEquals(['Discussion 3', 'Discussion 2'], $second['titles']);

        $this->assertEquals(4, $third['offset']);
        $this->assertEquals(['Discussion 1'], $third['titles']);
    }
}
