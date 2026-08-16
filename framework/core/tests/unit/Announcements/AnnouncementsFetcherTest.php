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
use GuzzleHttp\Middleware;
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

    /**
     * @param array $history Populated with the requests actually sent, so a test
     *                       can assert what was asked for and not only what came
     *                       back.
     */
    private function makeFetcher(array $responses, array &$history = []): AnnouncementsFetcher
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($history));

        $client = new Client(['handler' => $stack]);

        $fetcher = new AnnouncementsFetcher($this->appInfo);

        // Inject the mock client via reflection
        $ref = new \ReflectionProperty($fetcher, 'client');
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

    /**
     * Build the `tags` relationship + matching `included` resources for a set of
     * tag slugs, so a discussion can be tagged in a test.
     *
     * @param string[] $slugs
     * @return array{0: array<string, mixed>, 1: array<int, array<string, mixed>>}
     */
    private function tags(array $slugs): array
    {
        $relationship = ['tags' => ['data' => []]];
        $included = [];

        foreach ($slugs as $slug) {
            $relationship['tags']['data'][] = ['type' => 'tags', 'id' => $slug];
            $included[] = ['type' => 'tags', 'id' => $slug, 'attributes' => ['slug' => $slug]];
        }

        return [$relationship, $included];
    }

    public function test_excludes_old_line_only_news(): void
    {
        [$rel, $included] = $this->tags(['blog', 'version-1x']);

        $fetcher = $this->makeFetcher([
            $this->makeApiResponse(
                [$this->makeDiscussion(['id' => '1', 'title' => '1.x patch', 'slug' => 'onex'], $rel)],
                $included
            ),
        ]);

        $this->assertCount(0, $fetcher->fetch(), 'A 1.x-only post must be dropped from a 2.x forum feed.');
    }

    public function test_keeps_dual_tagged_news(): void
    {
        [$rel, $included] = $this->tags(['blog', 'version-1x', 'version-2x']);

        $fetcher = $this->makeFetcher([
            $this->makeApiResponse(
                [$this->makeDiscussion(['id' => '1', 'title' => 'Applies to both', 'slug' => 'both'], $rel)],
                $included
            ),
        ]);

        $result = $fetcher->fetch();

        $this->assertCount(1, $result, 'A post tagged for both lines must survive the exclusion.');
        $this->assertEquals('Applies to both', $result[0]['title']);
    }

    public function test_keeps_version_neutral_news(): void
    {
        [$rel, $included] = $this->tags(['blog', 'meta']);

        $fetcher = $this->makeFetcher([
            $this->makeApiResponse(
                [$this->makeDiscussion(['id' => '1', 'title' => 'Community update', 'slug' => 'cu'], $rel)],
                $included
            ),
        ]);

        $this->assertCount(1, $fetcher->fetch(), 'A post with no version tag must always be kept.');
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

    /**
     * The excerpt and author are read out of `included`, which only arrives if
     * the request asks for those relationships. Every other test here hands the
     * transform a well-formed `included` array, so none of them would notice the
     * request losing its `include`.
     */
    public function test_requests_the_relationships_the_excerpt_and_author_need(): void
    {
        $history = [];
        $fetcher = $this->makeFetcher([$this->makeApiResponse([$this->makeDiscussion()])], $history);

        $fetcher->fetch();

        $this->assertCount(1, $history);

        $query = [];
        parse_str($history[0]['request']->getUri()->getQuery(), $query);

        $this->assertArrayHasKey('include', $query, 'The request did not ask for any relationships.');

        $includes = array_map('trim', explode(',', $query['include']));

        $this->assertContains('firstPost', $includes, 'Without firstPost there is no content to excerpt.');
        $this->assertContains('user', $includes, 'Without user there is no author name or avatar.');
        $this->assertContains('tags', $includes, 'Without tags the version-exclusion filter has nothing to read.');
    }

    /**
     * A well-formed response whose `included` is empty, because the relationship
     * was not serialized — which is what discuss.flarum.org returned while
     * fof/gamification narrowed the `firstPost` eager load, leaving every
     * announcement card in every forum's admin panel blank.
     *
     * "The excerpt never arrived" and "the post has no text" are different
     * things, and collapsing both to an empty string is why that went unnoticed.
     * Only the absence of an excerpt is reportable, so absence is what it says.
     */
    public function test_excerpt_is_null_when_the_first_post_was_not_included(): void
    {
        $fetcher = $this->makeFetcher([
            $this->makeApiResponse(
                [$this->makeDiscussion([], [
                    'firstPost' => ['data' => ['type' => 'posts', 'id' => '10']],
                ])],
                // Declared on the discussion, absent from `included`.
                []
            ),
        ]);

        $result = $fetcher->fetch();

        $this->assertNull(
            $result[0]['excerpt'],
            'A missing include produced an empty-string excerpt, indistinguishable from a post with no content.'
        );
    }

    /**
     * A post that genuinely has no text still reports an empty excerpt rather
     * than null, so the two cases stay distinguishable in both directions.
     */
    public function test_excerpt_is_empty_when_the_first_post_has_no_content(): void
    {
        $fetcher = $this->makeFetcher([
            $this->makeApiResponse(
                [$this->makeDiscussion([], [
                    'firstPost' => ['data' => ['type' => 'posts', 'id' => '10']],
                ])],
                [[
                    'type' => 'posts',
                    'id' => '10',
                    'attributes' => ['contentHtml' => ''],
                ]]
            ),
        ]);

        $result = $fetcher->fetch();

        $this->assertSame('', $result[0]['excerpt']);
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
