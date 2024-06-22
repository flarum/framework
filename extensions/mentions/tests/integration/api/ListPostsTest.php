<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Tests\integration\api\discussions;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Mentions\Api\PostResourceFields;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;

class ListPostsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mentions');

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>text</p></t>'],
                ['id' => 2, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>text</p></t>'],
                ['id' => 3, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>text</p></t>'],
                ['id' => 4, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>text</p></t>'],
            ],
            'post_mentions_user' => [
                ['post_id' => 2, 'mentions_user_id' => 1],
                ['post_id' => 3, 'mentions_user_id' => 1],
                ['post_id' => 4, 'mentions_user_id' => 2]
            ],
            User::class => [
                $this->normalUser(),
            ]
        ]);
    }

    /**
     * @test
     */
    public function mentioned_filter_works()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts')
                ->withQueryParams([
                    'filter' => ['mentioned' => 1],
                ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $ids = Arr::pluck($data, 'id');
        $this->assertEqualsCanonicalizing(['2', '3'], $ids, 'IDs do not match');
    }

    /**
     * @test
     */
    public function mentioned_filter_works_negated()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts')
            ->withQueryParams([
                'filter' => ['-mentioned' => 1],
            ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $ids = Arr::pluck($data, 'id');
        $this->assertEqualsCanonicalizing(['4'], $ids, 'IDs do not match');
    }

    /**
     * @test
     */
    public function mentioned_filter_works_with_sort()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts')
                ->withQueryParams([
                    'filter' => ['mentioned' => 1],
                    'sort' => '-createdAt'
                ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        $this->assertEquals(200, $response->getStatusCode());

        // Order-independent comparison
        $ids = Arr::pluck($data, 'id');
        $this->assertEqualsCanonicalizing(['3', '2'], $ids, 'IDs do not match');
    }

    protected function prepareMentionedByData(): void
    {
        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 100, 'title' => __CLASS__, 'created_at' => Carbon::parse('2024-05-04'), 'user_id' => 1, 'first_post_id' => 101, 'comment_count' => 12],
            ],
            Post::class => [
                ['id' => 101, 'discussion_id' => 100, 'created_at' => Carbon::parse('2024-05-04'), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>text</p></t>'],
                ['id' => 102, 'discussion_id' => 100, 'created_at' => Carbon::parse('2024-05-04')->addMinutes(2), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>text</p></t>'],
                ['id' => 103, 'discussion_id' => 100, 'created_at' => Carbon::parse('2024-05-04')->addMinutes(3), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>text</p></t>', 'is_private' => 1],
                ['id' => 104, 'discussion_id' => 100, 'created_at' => Carbon::parse('2024-05-04')->addMinutes(4), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>text</p></t>'],
                ['id' => 105, 'discussion_id' => 100, 'created_at' => Carbon::parse('2024-05-04')->addMinutes(5), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>text</p></t>'],
                ['id' => 106, 'discussion_id' => 100, 'created_at' => Carbon::parse('2024-05-04')->addMinutes(6), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>text</p></t>'],
                ['id' => 107, 'discussion_id' => 100, 'created_at' => Carbon::parse('2024-05-04')->addMinutes(7), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>text</p></t>'],
                ['id' => 108, 'discussion_id' => 100, 'created_at' => Carbon::parse('2024-05-04')->addMinutes(8), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>text</p></t>'],
                ['id' => 109, 'discussion_id' => 100, 'created_at' => Carbon::parse('2024-05-04')->addMinutes(9), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>text</p></t>'],
                ['id' => 110, 'discussion_id' => 100, 'created_at' => Carbon::parse('2024-05-04')->addMinutes(10), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>text</p></t>'],
                ['id' => 111, 'discussion_id' => 100, 'created_at' => Carbon::parse('2024-05-04')->addMinutes(11), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>text</p></t>'],
                ['id' => 112, 'discussion_id' => 100, 'created_at' => Carbon::parse('2024-05-04')->addMinutes(12), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>text</p></t>'],
            ],
            'post_mentions_post' => [
                ['post_id' => 102, 'mentions_post_id' => 101, 'created_at' => Carbon::parse('2024-05-04')->addMinutes(2)],
                ['post_id' => 103, 'mentions_post_id' => 101, 'created_at' => Carbon::parse('2024-05-04')->addMinutes(3)],
                ['post_id' => 104, 'mentions_post_id' => 101, 'created_at' => Carbon::parse('2024-05-04')->addMinutes(4)],
                ['post_id' => 105, 'mentions_post_id' => 101, 'created_at' => Carbon::parse('2024-05-04')->addMinutes(5)],
                ['post_id' => 106, 'mentions_post_id' => 101, 'created_at' => Carbon::parse('2024-05-04')->addMinutes(6)],
                ['post_id' => 107, 'mentions_post_id' => 101, 'created_at' => Carbon::parse('2024-05-04')->addMinutes(7)],
                ['post_id' => 108, 'mentions_post_id' => 101, 'created_at' => Carbon::parse('2024-05-04')->addMinutes(8)],
                ['post_id' => 109, 'mentions_post_id' => 101, 'created_at' => Carbon::parse('2024-05-04')->addMinutes(9)],
                ['post_id' => 110, 'mentions_post_id' => 101, 'created_at' => Carbon::parse('2024-05-04')->addMinutes(10)],
                ['post_id' => 111, 'mentions_post_id' => 101, 'created_at' => Carbon::parse('2024-05-04')->addMinutes(11)],
                ['post_id' => 112, 'mentions_post_id' => 101, 'created_at' => Carbon::parse('2024-05-04')->addMinutes(12)],
                ['post_id' => 103, 'mentions_post_id' => 112, 'created_at' => Carbon::parse('2024-05-04')->addMinutes(13)],
            ],
        ]);
    }

    /** @test */
    public function mentioned_by_relation_returns_limited_results_and_shows_only_visible_posts_in_show_post_endpoint()
    {
        $this->prepareMentionedByData();

        // List posts endpoint
        $response = $this->send(
            $this->request('GET', '/api/posts/101', [
                'authenticatedAs' => 2,
            ])->withQueryParams([
                'include' => 'mentionedBy',
            ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        $this->assertEquals(200, $response->getStatusCode());

        $mentionedBy = $data['relationships']['mentionedBy']['data'];

        // Only displays a limited amount of mentioned by posts
        $this->assertCount(PostResourceFields::$maxMentionedBy, $mentionedBy);
        // Of the limited amount of mentioned by posts, they must be visible to the actor
        $this->assertEquals([102, 104, 105, 106], Arr::pluck($mentionedBy, 'id'));
    }

    /** @test */
    public function mentioned_by_relation_returns_limited_results_and_shows_only_visible_posts_in_list_posts_endpoint()
    {
        $this->prepareMentionedByData();

        // List posts endpoint
        $response = $this->send(
            $this->request('GET', '/api/posts', [
                'authenticatedAs' => 2,
            ])->withQueryParams([
                'filter' => ['discussion' => 100],
                'include' => 'mentionedBy',
                'sort' => 'createdAt',
            ])
        );

        $data = json_decode($body = $response->getBody()->getContents(), true)['data'];

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $mentionedBy = $data[0]['relationships']['mentionedBy']['data'];

        // Only displays a limited amount of mentioned by posts
        $this->assertCount(PostResourceFields::$maxMentionedBy, $mentionedBy);
        // Of the limited amount of mentioned by posts, they must be visible to the actor
        $this->assertEquals([102, 104, 105, 106], Arr::pluck($mentionedBy, 'id'));
    }

    /**
     * @dataProvider mentionedByIncludeProvider
     * @test
     */
    public function mentioned_by_relation_returns_limited_results_and_shows_only_visible_posts_in_show_discussion_endpoint(?string $include)
    {
        $this->prepareMentionedByData();

        // Show discussion endpoint
        $response = $this->send(
            $this->request('GET', '/api/discussions/100', [
                'authenticatedAs' => 2,
            ])->withQueryParams([
                'include' => $include,
            ])
        );

        $included = json_decode($body = $response->getBody()->getContents(), true)['included'] ?? [];

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $mentionedBy = collect($included)
            ->where('type', 'posts')
            ->where('id', 101)
            ->first()['relationships']['mentionedBy']['data'] ?? null;

        $this->assertNotNull($mentionedBy, 'Mentioned by relation not included');
        // Only displays a limited amount of mentioned by posts
        $this->assertCount(PostResourceFields::$maxMentionedBy, $mentionedBy);
        // Of the limited amount of mentioned by posts, they must be visible to the actor
        $this->assertEquals([102, 104, 105, 106], Arr::pluck($mentionedBy, 'id'));
    }

    public function mentionedByIncludeProvider(): array
    {
        return [
            ['posts,posts.mentionedBy'],
            ['posts.mentionedBy'],
            [null],
        ];
    }

    /** @test */
    public function mentioned_by_count_only_includes_visible_posts_to_actor()
    {
        $this->prepareMentionedByData();

        // List posts endpoint
        $response = $this->send(
            $this->request('GET', '/api/posts/112', [
                'authenticatedAs' => 2,
            ])
        );

        $data = json_decode($body = $response->getBody()->getContents(), true)['data'] ?? [];

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $this->assertEquals(0, $data['attributes']['mentionedByCount']);
    }

    /** @test */
    public function mentioned_by_count_works_on_show_endpoint()
    {
        $this->prepareMentionedByData();

        // List posts endpoint
        $response = $this->send(
            $this->request('GET', '/api/posts/101', [
                'authenticatedAs' => 1,
            ])
        );

        $data = json_decode($body = $response->getBody()->getContents(), true)['data'] ?? [];

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $this->assertEquals(10, $data['attributes']['mentionedByCount']);
    }

    /** @test */
    public function mentioned_by_count_works_on_list_endpoint()
    {
        $this->prepareMentionedByData();

        // List posts endpoint
        $response = $this->send(
            $this->request('GET', '/api/posts', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'filter' => ['discussion' => 100],
            ])
        );

        $data = json_decode($body = $response->getBody()->getContents(), true)['data'] ?? [];

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $post101 = collect($data)->where('id', 101)->first();
        $post112 = collect($data)->where('id', 112)->first();

        $this->assertEquals(10, $post101['attributes']['mentionedByCount']);
        $this->assertEquals(0, $post112['attributes']['mentionedByCount']);
    }
}
