<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Announcements;

use Flarum\Announcements\AnnouncementsFetcher;
use Flarum\Foundation\ApplicationInfoProvider;
use Flarum\Testing\unit\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery as m;

class AnnouncementsFetcherTest extends TestCase
{
    private ApplicationInfoProvider $appInfo;

    protected function setUp(): void
    {
        $this->appInfo = m::mock(ApplicationInfoProvider::class);
        $this->appInfo->shouldReceive('identifyPHPVersion')->andReturn('8.3.0');
        $this->appInfo->shouldReceive('identifyDatabaseDriver')->andReturn('MySQL');
        $this->appInfo->shouldReceive('identifyDatabaseVersion')->andReturn('8.0.32');
    }

    private function makeFetcher(array $responses): AnnouncementsFetcher
    {
        $mock = new MockHandler($responses);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $fetcher = new AnnouncementsFetcher($this->appInfo);

        // Inject the mock client via reflection
        $ref = new \ReflectionProperty($fetcher, 'client');
        $ref->setAccessible(true);
        $ref->setValue($fetcher, $client);

        return $fetcher;
    }

    private function makeApiResponse(array $discussions, array $included = []): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'data' => $discussions,
            'included' => $included,
        ]));
    }

    private function makeDiscussion(array $attrs = [], array $relationships = []): array
    {
        return [
            'id' => $attrs['id'] ?? '1',
            'type' => 'discussions',
            'attributes' => array_merge([
                'title' => 'Test Discussion',
                'slug' => 'test-discussion',
                'commentCount' => 5,
                'createdAt' => '2026-01-01T00:00:00+00:00',
                'isSticky' => false,
            ], $attrs),
            'relationships' => $relationships,
        ];
    }

    public function test_transforms_discussion_to_expected_shape(): void
    {
        $fetcher = $this->makeFetcher([
            $this->makeApiResponse([$this->makeDiscussion()]),
        ]);

        $result = $fetcher->fetch();

        $this->assertCount(1, $result);
        $this->assertEquals('1', $result[0]['id']);
        $this->assertEquals('Test Discussion', $result[0]['title']);
        $this->assertEquals('test-discussion', $result[0]['slug']);
        $this->assertEquals(5, $result[0]['commentCount']);
        $this->assertEquals('https://discuss.flarum.org/d/test-discussion', $result[0]['url']);
        $this->assertFalse($result[0]['isSticky']);
        $this->assertNull($result[0]['authorName']);
        $this->assertNull($result[0]['avatarUrl']);
    }

    public function test_sticky_discussions_sorted_first(): void
    {
        $fetcher = $this->makeFetcher([
            $this->makeApiResponse([
                $this->makeDiscussion(['id' => '1', 'title' => 'Regular', 'slug' => 'regular', 'isSticky' => false]),
                $this->makeDiscussion(['id' => '2', 'title' => 'Sticky', 'slug' => 'sticky', 'isSticky' => true]),
                $this->makeDiscussion(['id' => '3', 'title' => 'Also Regular', 'slug' => 'also-regular', 'isSticky' => false]),
            ]),
        ]);

        $result = $fetcher->fetch();

        $this->assertEquals('2', $result[0]['id']);
        $this->assertEquals('1', $result[1]['id']);
        $this->assertEquals('3', $result[2]['id']);
    }

    public function test_resolves_author_from_included(): void
    {
        $fetcher = $this->makeFetcher([
            $this->makeApiResponse(
                [$this->makeDiscussion([], [
                    'user' => ['data' => ['type' => 'users', 'id' => '42']],
                ])],
                [[
                    'type' => 'users',
                    'id' => '42',
                    'attributes' => ['displayName' => 'IanM', 'avatarUrl' => 'https://example.com/avatar.jpg'],
                ]]
            ),
        ]);

        $result = $fetcher->fetch();

        $this->assertEquals('IanM', $result[0]['authorName']);
        $this->assertEquals('https://example.com/avatar.jpg', $result[0]['avatarUrl']);
    }

    public function test_resolves_excerpt_from_first_post(): void
    {
        $fetcher = $this->makeFetcher([
            $this->makeApiResponse(
                [$this->makeDiscussion([], [
                    'firstPost' => ['data' => ['type' => 'posts', 'id' => '99']],
                ])],
                [[
                    'type' => 'posts',
                    'id' => '99',
                    'attributes' => ['contentHtml' => '<p>Hello <strong>world</strong> this is content.</p>'],
                ]]
            ),
        ]);

        $result = $fetcher->fetch();

        $this->assertEquals('Hello world this is content.', $result[0]['excerpt']);
    }

    public function test_excerpt_is_truncated_to_200_chars(): void
    {
        $longText = str_repeat('a', 250);

        $fetcher = $this->makeFetcher([
            $this->makeApiResponse(
                [$this->makeDiscussion([], [
                    'firstPost' => ['data' => ['type' => 'posts', 'id' => '1']],
                ])],
                [[
                    'type' => 'posts',
                    'id' => '1',
                    'attributes' => ['contentHtml' => $longText],
                ]]
            ),
        ]);

        $result = $fetcher->fetch();

        $this->assertLessThanOrEqual(201, mb_strlen($result[0]['excerpt'])); // 200 chars + ellipsis
        $this->assertStringEndsWith('…', $result[0]['excerpt']);
    }

    public function test_skips_discussions_missing_required_fields(): void
    {
        $fetcher = $this->makeFetcher([
            $this->makeApiResponse([
                $this->makeDiscussion(['title' => 'Valid', 'slug' => 'valid']),
                // Missing title
                ['id' => '2', 'type' => 'discussions', 'attributes' => ['slug' => 'no-title', 'createdAt' => '2026-01-01T00:00:00+00:00'], 'relationships' => []],
                // Missing slug
                ['id' => '3', 'type' => 'discussions', 'attributes' => ['title' => 'No Slug', 'createdAt' => '2026-01-01T00:00:00+00:00'], 'relationships' => []],
            ]),
        ]);

        $result = $fetcher->fetch();

        $this->assertCount(1, $result);
        $this->assertEquals('Valid', $result[0]['title']);
    }

    public function test_throws_on_network_failure(): void
    {
        $fetcher = $this->makeFetcher([
            new ConnectException('Connection refused', new Request('GET', 'test')),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Could not fetch announcements/');

        $fetcher->fetch();
    }

    public function test_throws_on_garbled_response(): void
    {
        $fetcher = $this->makeFetcher([
            new Response(200, ['Content-Type' => 'application/json'], 'not valid json at all'),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Unexpected response/');

        $fetcher->fetch();
    }

    public function test_throws_on_missing_data_key(): void
    {
        $fetcher = $this->makeFetcher([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['meta' => []])),
        ]);

        $this->expectException(\RuntimeException::class);

        $fetcher->fetch();
    }

    public function test_results_sliced_to_limit(): void
    {
        $discussions = array_map(fn ($i) => $this->makeDiscussion([
            'id' => (string) $i,
            'title' => "Discussion $i",
            'slug' => "discussion-$i",
        ]), range(1, 20));

        $fetcher = $this->makeFetcher([
            $this->makeApiResponse($discussions),
        ]);

        $result = $fetcher->fetch();

        $this->assertCount(8, $result);
    }
}
