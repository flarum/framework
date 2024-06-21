<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\discussions;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Arr;

class ListWithFulltextSearchTest extends TestCase
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
        $this->database()->table('discussions')->insert($this->rowsThroughFactory(Discussion::class, [
            ['id' => 1, 'title' => 'lightsail in title', 'user_id' => 1],
            ['id' => 2, 'title' => 'lightsail in title too', 'created_at' => Carbon::createFromDate(2020, 01, 01)->toDateTimeString(), 'user_id' => 1],
            ['id' => 3, 'title' => 'not in title either', 'user_id' => 1],
            ['id' => 4, 'title' => 'not in title or text', 'user_id' => 1],
            ['id' => 5, 'title' => 'తెలుగు', 'user_id' => 1],
            ['id' => 6, 'title' => '支持中文吗', 'user_id' => 1],
        ]));

        $this->database()->table('posts')->insert($this->rowsThroughFactory(Post::class, [
            ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'content' => '<t><p>not in text</p></t>'],
            ['id' => 2, 'discussion_id' => 2, 'user_id' => 1, 'content' => '<t><p>lightsail in text</p></t>'],
            ['id' => 3, 'discussion_id' => 2, 'user_id' => 1, 'content' => '<t><p>another lightsail for discussion 2!</p></t>'],
            ['id' => 4, 'discussion_id' => 3, 'user_id' => 1, 'content' => '<t><p>just one lightsail for discussion 3.</p></t>'],
            ['id' => 5, 'discussion_id' => 4, 'user_id' => 1, 'content' => '<t><p>not in title or text</p></t>'],
            ['id' => 6, 'discussion_id' => 4, 'user_id' => 1, 'content' => '<t><p>తెలుగు</p></t>'],
            ['id' => 7, 'discussion_id' => 2, 'user_id' => 1, 'content' => '<t><p>支持中文吗</p></t>'],
        ]));

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

        $this->database()->table('discussions')->delete();
        $this->database()->table('posts')->delete();
    }

    /**
     * @test
     */
    public function can_search_for_word_or_title_in_post()
    {
        if ($this->database()->getDriverName() === 'sqlite') {
            return $this->markTestSkipped('No fulltext search in SQLite.');
        }

        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams([
                    'filter' => ['q' => 'lightsail'],
                    'include' => 'mostRelevantPost',
                ])
        );

        $data = json_decode($response->getBody()->getContents(), true);
        $ids = array_map(function ($row) {
            return $row['id'];
        }, $data['data']);

        $this->assertEqualsCanonicalizing(['2', '1', '3'], $ids, 'IDs do not match');
    }

    /**
     * @test
     */
    public function ignores_non_word_characters_when_searching()
    {
        if ($this->database()->getDriverName() === 'sqlite') {
            return $this->markTestSkipped('No fulltext search in SQLite.');
        }

        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams([
                    'filter' => ['q' => 'lightsail+'],
                    'include' => 'mostRelevantPost',
                ])
        );

        $data = json_decode($response->getBody()->getContents(), true);
        $ids = array_map(function ($row) {
            return $row['id'];
        }, $data['data']);

        $this->assertEqualsCanonicalizing(['2', '1', '3'], $ids, 'IDs do not match');
    }

    /**
     * @test
     */
    public function can_search_telugu_like_languages()
    {
        if ($this->database()->getDriverName() === 'sqlite') {
            return $this->markTestSkipped('No fulltext search in SQLite.');
        }

        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams([
                    'filter' => ['q' => 'తెలుగు'],
                    'include' => 'mostRelevantPost',
                ])
        );

        $data = json_decode($response->getBody()->getContents(), true);
        $ids = array_map(function ($row) {
            return $row['id'];
        }, $data['data']);

        $this->assertEqualsCanonicalizing(['4', '5'], $ids, 'IDs do not match');
        $this->assertEqualsCanonicalizing(['6'], Arr::pluck($data['included'], 'id'));
    }

    /**
     * @test
     */
    public function can_search_cjk_languages()
    {
        if ($this->database()->getDriverName() === 'sqlite') {
            return $this->markTestSkipped('No fulltext search in SQLite.');
        }

        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams([
                    'filter' => ['q' => '支持中文吗'],
                    'include' => 'mostRelevantPost',
                ])
        );

        $data = json_decode($response->getBody()->getContents(), true);
        $ids = array_map(function ($row) {
            return $row['id'];
        }, $data['data']);

        $this->assertEqualsCanonicalizing(['2', '6'], $ids, 'IDs do not match');
        $this->assertEqualsCanonicalizing(['7'], Arr::pluck($data['included'], 'id'));
    }

    /**
     * @test
     */
    public function search_for_special_characters_gives_empty_result()
    {
        if ($this->database()->getDriverName() === 'sqlite') {
            return $this->markTestSkipped('No fulltext search in SQLite.');
        }

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
