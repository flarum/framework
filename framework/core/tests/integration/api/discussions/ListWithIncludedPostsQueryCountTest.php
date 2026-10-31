<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\discussions;

use Carbon\Carbon;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Database\ConnectionInterface;
use PHPUnit\Framework\Attributes\Test;

class ListWithIncludedPostsQueryCountTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private const DISCUSSION_COUNT = 10;

    protected function setUp(): void
    {
        parent::setUp();

        $discussions = [];
        $posts = [];
        $now = Carbon::now();

        for ($i = 1; $i <= self::DISCUSSION_COUNT; $i++) {
            $discussions[] = [
                'id' => $i, 'title' => "Discussion $i", 'created_at' => $now,
                'user_id' => 1, 'first_post_id' => $i, 'comment_count' => 1,
            ];
            $posts[] = [
                'id' => $i, 'discussion_id' => $i, 'created_at' => $now,
                'user_id' => 1, 'type' => 'comment', 'number' => 1,
                'content' => '<t><p>first post</p></t>',
            ];
        }

        $this->prepareDatabase([
            Discussion::class => $discussions,
            Post::class => $posts,
            User::class => [$this->normalUser()],
        ]);
    }

    #[Test]
    public function included_first_posts_reuse_the_discussion_being_listed(): void
    {
        $db = $this->database();
        $db->enableQueryLog();

        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
                ->withQueryParams(['include' => 'firstPost'])
        );

        $this->assertEquals(200, $response->getStatusCode());

        // Serializing each included first post runs visibility checks
        // (canEdit, canHide, ...) that read the post's discussion. The
        // discussion is the very model being listed, so it must be reused,
        // not fetched again one row at a time per post.
        $singleFetches = array_filter($db->getQueryLog(), function (array $query) {
            $sql = str_replace(['`', '"', '[', ']'], '"', $query['query']);

            return str_contains($sql, 'from "discussions" where "discussions"."id" = ');
        });

        $this->assertCount(
            0,
            $singleFetches,
            'Included posts should reuse the listed discussion instead of re-fetching it per post.'
        );
    }

    #[Test]
    public function posts_preloaded_by_endpoint_eager_loads_reuse_the_listed_discussion(): void
    {
        // Unlike the buffer-loaded case above, a relation PRE-loaded by an
        // endpoint eager load arrives via loadMissing() — a path that
        // historically set no inverse. The relationship buffer then sees the
        // relation as loaded and skips it entirely, so nothing wired the post
        // back to its discussion and every visibility check re-fetched it,
        // one row per post. This extender reproduces what sticky+geoip,
        // mentions and likes all do: eager load something UNDER an included
        // post relation.
        $this->extend(
            (new Extend\ApiResource(Resource\DiscussionResource::class))
                ->endpoint(Endpoint\Index::class, function (Endpoint\Index $endpoint): Endpoint\Index {
                    return $endpoint->eagerLoadWhenIncluded(['firstPost' => ['firstPost.user']]);
                })
        );

        $db = $this->database();
        $db->enableQueryLog();

        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
                ->withQueryParams(['include' => 'firstPost'])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $singleFetches = array_filter($db->getQueryLog(), function (array $query) {
            $sql = str_replace(['`', '"', '[', ']'], '"', $query['query']);

            return str_contains($sql, 'from "discussions" where "discussions"."id" = ');
        });

        $this->assertCount(
            0,
            $singleFetches,
            'Pre-loaded included posts should reuse the listed discussion instead of re-fetching it per post.'
        );
    }

    #[Test]
    public function included_first_posts_are_still_serialized(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
                ->withQueryParams(['include' => 'firstPost'])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        $includedPosts = array_filter($body['included'] ?? [], fn (array $r) => $r['type'] === 'posts');

        $this->assertCount(self::DISCUSSION_COUNT, $includedPosts);

        // And each discussion still links to its own first post.
        foreach ($body['data'] as $discussion) {
            $this->assertSame(
                $discussion['id'],
                $discussion['relationships']['firstPost']['data']['id'],
                'Fixture links discussion id N to post id N; the inverse wiring must not change linkage.'
            );
        }
    }

    protected function database(): ConnectionInterface
    {
        return parent::database();
    }
}
