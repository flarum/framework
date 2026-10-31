<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\posts;

use Carbon\Carbon;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\PostResource;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Post\Post;
use Flarum\Testing\integration\RepeatedQueryDetector;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * A discussion reached a second time, through another include path that holds
 * another instance of it, must not load the actor's read state again.
 *
 * The posts index eager loads `discussion.state`, so the first visit has it.
 * When the actor has never read a discussion, `lastReadAt` and
 * `lastReadPostNumber` are hidden, and the serializer used to check them again
 * on the second visit: on an instance with no `state` loaded, so one
 * `discussion_user` query per unread discussion. Post lists with their default
 * includes do this for every discussion through `mentionedBy.discussion`.
 */
class RevisitedDiscussionStateQueryCountTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * The actor has read the odd-numbered half. Half the page is then unread,
     * four discussions: below the repeated-query threshold, so a failure
     * reports this test's own assertions.
     */
    private const DISCUSSIONS = 8;

    private const INCLUDE = 'discussion,discussion.firstPost,discussion.firstPost.discussion';

    protected function setUp(): void
    {
        parent::setUp();

        $discussions = [];
        $posts = [];
        $states = [];

        for ($i = 1; $i <= self::DISCUSSIONS; $i++) {
            $discussions[] = ['id' => $i, 'title' => "Discussion $i", 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => $i, 'comment_count' => 2, 'last_post_number' => 2];
            $posts[] = ['id' => $i, 'discussion_id' => $i, 'number' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Opening post</p></t>'];
            $posts[] = ['id' => 100 + $i, 'discussion_id' => $i, 'number' => 2, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>Reply</p></t>'];

            if ($i % 2) {
                $states[] = ['user_id' => 2, 'discussion_id' => $i, 'last_read_post_number' => 2, 'last_read_at' => Carbon::now()];
            }
        }

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'author', 'email' => 'author@machine.local', 'is_email_confirmed' => 1, 'password' => 'foobar'],
            ],
            Discussion::class => $discussions,
            Post::class => $posts,
            'discussion_user' => $states,
        ]);

        // The second path is pre-loaded with fresh models, as extensions do
        // under their includes (mentions' `mentionedBy` loads its own
        // `discussion`). Left to the relationship buffer, the first post would
        // point back at the listed discussion and share its loaded state.
        $this->extend(
            (new Extend\ApiResource(PostResource::class))
                ->endpoint(Endpoint\Index::class, function (Endpoint\Index $endpoint): Endpoint\Index {
                    return $endpoint->eagerLoadWhenIncluded([
                        'discussion.firstPost.discussion' => ['discussion.firstPost.discussion'],
                    ]);
                })
        );
    }

    #[Test]
    public function a_discussion_reached_twice_does_not_load_its_state_again(): void
    {
        [$document, $queries] = $this->listPosts(self::DISCUSSIONS);

        $this->assertSame(
            [],
            $this->discussionStateLookups($queries),
            'A discussion\'s read state was looked up one at a time when the discussion was reached again.'
        );

        // The state still reaches the client for the discussions that have one.
        $discussions = collect($document['included'])->where('type', 'discussions')->keyBy('id');

        $this->assertCount(self::DISCUSSIONS, $discussions);

        foreach ($discussions as $id => $discussion) {
            if ($id % 2) {
                $this->assertSame(2, $discussion['attributes']['lastReadPostNumber'] ?? null, "Discussion $id lost its read state.");
                $this->assertArrayHasKey('lastReadAt', $discussion['attributes']);
            } else {
                $this->assertArrayNotHasKey('lastReadPostNumber', $discussion['attributes'], "Discussion $id was never read.");
                $this->assertArrayNotHasKey('lastReadAt', $discussion['attributes']);
            }
        }
    }

    #[Test]
    public function the_query_count_does_not_grow_with_the_discussions_listed(): void
    {
        // The first request pays for what the app caches (settings and the
        // like); compare two that don't. Both pages hold read and unread
        // discussions, so they differ only in size.
        $this->listPosts(2);

        [, $few] = $this->listPosts(2);
        [, $many] = $this->listPosts(self::DISCUSSIONS);

        $this->assertSame(
            count($few),
            count($many),
            "Listing more discussions ran more queries:\n".$this->describeGrowth($few, $many)
        );
    }

    /**
     * @return array{0: array, 1: array<array{query: string, bindings: array}>}
     */
    private function listPosts(int $limit): array
    {
        $db = $this->database();
        $db->enableQueryLog();

        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 2])
                ->withQueryParams([
                    'filter' => ['author' => 'author'],
                    'include' => self::INCLUDE,
                    'page' => ['limit' => $limit],
                ])
        );

        $queries = $db->getQueryLog();
        $db->flushQueryLog();

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $document = json_decode($body, true);

        $this->assertCount($limit, $document['data']);

        return [$document, $queries];
    }

    /**
     * One discussion's read state, as a lazily loaded `state` relation does.
     * The batched eager load inlines its keys into `in (...)`, so it doesn't match.
     *
     * @return string[]
     */
    private function discussionStateLookups(array $queries): array
    {
        return array_values(preg_grep(
            '/["`]?(\w+_)?discussion_user["`]?\.["`]?discussion_id["`]? = \?/i',
            array_column($queries, 'query')
        ));
    }

    private function describeGrowth(array $few, array $many): string
    {
        $shapes = fn (array $queries) => array_count_values(array_map(
            fn (array $query) => RepeatedQueryDetector::normalise($query['query']),
            $queries
        ));

        $before = $shapes($few);
        $lines = [];

        foreach ($shapes($many) as $shape => $count) {
            if ($count !== ($before[$shape] ?? 0)) {
                $lines[] = sprintf('  %d -> %d: %s', $before[$shape] ?? 0, $count, $shape);
            }
        }

        return implode("\n", $lines);
    }
}
