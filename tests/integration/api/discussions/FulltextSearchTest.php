<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\discussions;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Arr;

class FulltextSearchTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->database()->rollBack();

        // We need to insert these outside of a transaction, because FULLTEXT indexing,
        // which is needed for search, doesn't happen in transactions.
        // We clean it up explcitly at the end.
        $this->database()->table('discussions')->insert([
            ['id' => 1, 'title' => 'lightsail in title', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'comment_count' => 1],
            ['id' => 2, 'title' => 'not in title', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'comment_count' => 1],
            ['id' => 3, 'title' => 'not in title', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'comment_count' => 1],
        ]);

        $this->database()->table('posts')->insert([
            ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>not in text</p></t>'],
            ['id' => 2, 'discussion_id' => 2, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>lightsail in text</p></t>'],
            ['id' => 3, 'discussion_id' => 3, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>not in text</p></t>'],
        ]);

        // We need to call these again, since we rolled back the transaction started by `::app()`.
        $this->database()->beginTransaction();

        $this->populateDatabase();
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->database()->table('discussions')->whereIn('id', [1, 2, 3])->delete();
        $this->database()->table('posts')->whereIn('id', [1, 2, 3])->delete();
    }

    /**
     * @test
     */
    public function can_search_for_word_or_title_in_post()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams([
                    'filter' => ['q' => 'lightsail'],
                    'include' => 'mostRelevantPost',
                ])
        );

        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEqualsCanonicalizing(['1', '2'], Arr::pluck($data['data'], 'id'), 'IDs do not match');
    }

    /**
     * @test
     */
    public function ignores_non_word_characters_when_searching()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams([
                    'filter' => ['q' => 'lightsail+'],
                    'include' => 'mostRelevantPost',
                ])
        );

        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEqualsCanonicalizing(['1', '2'], Arr::pluck($data['data'], 'id'), 'IDs do not match');
    }

    /**
     * @test
     */
    public function search_for_special_characters_gives_empty_result()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams([
                    'filter' => ['q' => '*'],
                    'include' => 'mostRelevantPost',
                ])
        );

        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals([], $data['data']);

        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams([
                    'filter' => ['q' => '@'],
                    'include' => 'mostRelevantPost',
                ])
        );

        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals([], $data['data']);
    }
}
