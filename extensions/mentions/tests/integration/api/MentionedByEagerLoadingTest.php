<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Database\ConnectionInterface;
use PHPUnit\Framework\Attributes\Test;

class MentionedByEagerLoadingTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mentions');

        $posts = [];
        $mentions = [];

        // Ten posts, each mentioned by a later one. Every "mentionedBy" post
        // gets visibility-checked during serialization, which is where the
        // discussion relation used to be lazily fetched one post at a time.
        for ($i = 1; $i <= 10; $i++) {
            $posts[] = [
                'id' => $i, 'discussion_id' => 1, 'created_at' => Carbon::now(),
                'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>text</p></t>',
            ];
        }

        for ($i = 11; $i <= 20; $i++) {
            $posts[] = [
                'id' => $i, 'discussion_id' => 1, 'created_at' => Carbon::now(),
                'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>text</p></t>',
            ];
            $mentions[] = ['post_id' => $i, 'mentions_post_id' => $i - 10];
        }

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 20],
            ],
            Post::class => $posts,
            'post_mentions_post' => $mentions,
            User::class => [$this->normalUser()],
        ]);
    }

    #[Test]
    public function listing_posts_does_not_lazy_load_a_discussion_per_mentioning_post(): void
    {
        $queries = $this->countQueriesFor(function () {
            return $this->send(
                $this->request('GET', '/api/posts', ['authenticatedAs' => 2])
                    ->withQueryParams(['filter' => ['discussion' => 1], 'page' => ['limit' => 20]])
            );
        });

        // Each mentioned post is fetched with its discussion in the same load,
        // so the number of queries
        // does not grow with the number of mentions. Before this was fixed the
        // same discussion was fetched once per mentioning post.
        $discussionQueries = $this->countMatching($queries, 'from "discussions" where "discussions"."id" = ');

        $this->assertLessThanOrEqual(
            1,
            $discussionQueries,
            'The discussion should not be fetched once per mentioning post. Queries: '.implode("\n", $queries)
        );
    }

    #[Test]
    public function listing_posts_still_returns_the_mentioning_posts(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['discussion' => 1], 'page' => ['limit' => 20]])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        $mentionedBy = [];
        foreach ($body['data'] as $post) {
            foreach ($post['relationships']['mentionedBy']['data'] ?? [] as $related) {
                $mentionedBy[$post['id']][] = $related['id'];
            }
        }

        // Post 1 is mentioned by post 11, post 2 by post 12, and so on.
        $this->assertSame(['11'], $mentionedBy['1'] ?? null);
        $this->assertSame(['20'], $mentionedBy['10'] ?? null);
    }

    /**
     * @return string[] the SQL run while the callback executed
     */
    private function countQueriesFor(callable $callback): array
    {
        $queries = [];

        $this->app()->getContainer()->make(ConnectionInterface::class)
            ->listen(function ($query) use (&$queries) {
                $queries[] = $query->sql;
            });

        $callback();

        return $queries;
    }

    /**
     * @param string[] $queries
     */
    private function countMatching(array $queries, string $needle): int
    {
        // Identifier quoting differs between drivers, so compare loosely.
        $normalise = fn (string $sql) => str_replace(['`', '"', '[', ']'], '"', $sql);
        $needle = $normalise($needle);

        return count(array_filter($queries, fn (string $sql) => str_contains($normalise($sql), $needle)));
    }
}
