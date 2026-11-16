<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration\content;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Tags\Tag;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * A search on a tag's page (`/t/<slug>?q=…`) preloads the search's results,
 * not the tag's whole list: the frontend shows the preloaded discussions as
 * they are when the query holds no gambits (#5460).
 */
class TagSearchTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Tag::class => [
                ['id' => 1, 'name' => 'General', 'slug' => 'general', 'position' => 0],
            ],
        ]);

        $this->database()->rollBack();

        // Inserted outside a transaction, because MySQL and MariaDB only add
        // committed rows to their FULLTEXT indexes. Removed in `tearDown()`.
        $this->database()->table('discussions')->insert($this->rowsThroughFactory(Discussion::class, [
            ['id' => 1, 'title' => 'Apples and pears', 'user_id' => 1, 'last_posted_at' => Carbon::parse('2020-01-01')->toDateTimeString()],
            ['id' => 2, 'title' => 'Oranges and lemons', 'user_id' => 1, 'last_posted_at' => Carbon::parse('2021-01-01')->toDateTimeString()],
        ]));

        $this->database()->table('posts')->insert($this->rowsThroughFactory(Post::class, [
            ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'content' => '<t><p>Apples and pears</p></t>'],
            ['id' => 2, 'discussion_id' => 2, 'user_id' => 1, 'content' => '<t><p>Oranges and lemons</p></t>'],
        ]));

        $this->database()->table('discussion_tag')->insert([
            ['discussion_id' => 1, 'tag_id' => 1],
            ['discussion_id' => 2, 'tag_id' => 1],
        ]);

        // Again, since the transaction `::app()` started was rolled back.
        $this->database()->beginTransaction();

        $this->populateDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->database()->table('discussion_tag')->delete();
        $this->database()->table('posts')->delete();
        $this->database()->table('discussions')->delete();
    }

    /**
     * @return string[] Titles of the discussions the page preloaded.
     */
    protected function titles(array $query = []): array
    {
        $response = $this->send(
            $this->request('GET', '/t/general')->withQueryParams($query)
        );

        $this->assertEquals(200, $response->getStatusCode());

        preg_match('/<script id="flarum-json-payload" type="application\/json">(.+?)<\/script>/s', (string) $response->getBody(), $matches);

        $payload = json_decode(html_entity_decode($matches[1]), true);

        return array_values(array_map(
            fn (array $d) => $d['attributes']['title'],
            array_filter(
                $payload['apiDocument']['data'] ?? [],
                fn (array $d) => $d['type'] === 'discussions'
            )
        ));
    }

    #[Test]
    public function the_tag_page_without_a_search_lists_the_whole_tag()
    {
        $this->assertEquals(['Oranges and lemons', 'Apples and pears'], $this->titles());
    }

    #[Test]
    public function a_search_on_the_tag_page_preloads_only_its_results()
    {
        $this->assertEquals(['Apples and pears'], $this->titles(['q' => 'apples']));
    }
}
