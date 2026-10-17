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
use Flarum\Testing\integration\RepeatedQueryDetector;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * A user's profile lists their posts with the posts endpoint's default
 * includes, mentions' `mentionedBy.discussion` among them. A reply that
 * mentions a post is usually in the same discussion, so the profile reaches
 * each discussion twice: as the post's `discussion`, and as the mentioning
 * reply's, on another instance loaded by `mentionedBy` without the actor's
 * read state. Reaching it again must not look that state up per discussion.
 */
class ProfilePostListQueryCountTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * Below the repeated-query threshold, so a failure reports this test's own
     * assertions rather than the harness's N+1 detector.
     */
    protected const DISCUSSIONS = 4;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mentions');

        $this->prepareDatabase($this->profileFixture());
    }

    /**
     * Discussion k opens with a post by the admin (id k). The profiled author
     * replies (id 10 + k), and another member replies to that, mentioning it
     * (id 20 + k). The normal user has read the odd-numbered discussions.
     */
    protected function profileFixture(): array
    {
        $discussions = [];
        $posts = [];
        $mentions = [];
        $states = [];

        for ($k = 1; $k <= self::DISCUSSIONS; $k++) {
            $authorPost = 10 + $k;
            $reply = 20 + $k;

            $discussions[] = ['id' => $k, 'title' => "Discussion $k", 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => $k, 'last_post_id' => $reply, 'comment_count' => 3, 'last_post_number' => 3, 'participant_count' => 3];
            $posts[] = ['id' => $k, 'discussion_id' => $k, 'number' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Opening post</p></t>'];
            $posts[] = ['id' => $authorPost, 'discussion_id' => $k, 'number' => 2, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>The author\'s post</p></t>'];
            $posts[] = [
                'id' => $reply, 'discussion_id' => $k, 'number' => 3, 'created_at' => Carbon::now(), 'user_id' => 4, 'type' => 'comment',
                'content' => '<r><POSTMENTION displayname="author" id="'.$authorPost.'" number="2" discussionid="'.$k.'">@"author"#p'.$authorPost.'</POSTMENTION> Thanks</r>',
            ];
            $mentions[] = ['post_id' => $reply, 'mentions_post_id' => $authorPost];

            if ($k % 2) {
                $states[] = ['user_id' => 2, 'discussion_id' => $k, 'last_read_post_number' => 3, 'last_read_at' => Carbon::now()];
            }
        }

        return [
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'author', 'email' => 'author@machine.local', 'is_email_confirmed' => 1, 'password' => 'foobar'],
                ['id' => 4, 'username' => 'replier', 'email' => 'replier@machine.local', 'is_email_confirmed' => 1, 'password' => 'foobar'],
            ],
            Discussion::class => $discussions,
            Post::class => $posts,
            'post_mentions_post' => $mentions,
            'discussion_user' => $states,
        ];
    }

    public static function viewers(): array
    {
        return [
            // Never has a read state: every discussion is reached again with
            // its state hidden.
            'a guest' => [null],
            // Has read half of them.
            'a member' => [2],
        ];
    }

    #[Test]
    #[DataProvider('viewers')]
    public function a_profile_does_not_look_up_read_state_per_discussion(?int $viewer): void
    {
        [$document, $queries] = $this->listProfilePosts($viewer, self::DISCUSSIONS);

        $this->assertSame([], $this->discussionStateLookups($queries), 'A discussion\'s read state was looked up one at a time.');

        // What #5207 restored is still there: every post comes with the
        // replies that mention it, and those with their author and discussion.
        $included = collect($document['included'])->groupBy('type');

        foreach ($document['data'] as $post) {
            $k = (int) $post['id'] - 10;

            $this->assertSame([['type' => 'posts', 'id' => (string) (20 + $k)]], $post['relationships']['mentionedBy']['data'] ?? null, "Post {$post['id']} lost the reply that mentions it.");
            $this->assertSame((string) $k, $post['relationships']['discussion']['data']['id'] ?? null);
        }

        $replies = $included['posts']->keyBy('id');

        for ($k = 1; $k <= self::DISCUSSIONS; $k++) {
            $reply = $replies[(string) (20 + $k)] ?? null;

            $this->assertNotNull($reply, "The reply mentioning post ".(10 + $k).' is not included.');
            $this->assertSame('4', $reply['relationships']['user']['data']['id'] ?? null);
            $this->assertSame((string) $k, $reply['relationships']['discussion']['data']['id'] ?? null);
        }

        $this->assertContains('4', $included['users']->pluck('id')->all(), 'The replier is included.');

        $discussions = $included['discussions']->keyBy('id');

        $this->assertCount(self::DISCUSSIONS, $discussions);

        foreach ($discussions as $id => $discussion) {
            if ($viewer && $id % 2) {
                $this->assertSame(3, $discussion['attributes']['lastReadPostNumber'] ?? null, "Discussion $id lost its read state.");
            } else {
                $this->assertArrayNotHasKey('lastReadPostNumber', $discussion['attributes']);
            }
        }
    }

    #[Test]
    #[DataProvider('viewers')]
    public function a_profile_costs_the_same_number_of_queries_however_many_discussions_it_shows(?int $viewer): void
    {
        // The first request pays for what the app caches (settings and the
        // like); compare two that don't. The newest two posts are in
        // discussions 4 and 3, one read and one unread, so the pages differ
        // only in size.
        $this->listProfilePosts($viewer, 2);

        [, $few] = $this->listProfilePosts($viewer, 2);
        [, $many] = $this->listProfilePosts($viewer, self::DISCUSSIONS);

        $this->assertSame(
            count($few),
            count($many),
            "Listing posts from more discussions ran more queries:\n".$this->describeGrowth($few, $many)
        );
    }

    /**
     * The request PostsUserPage's PostListState sends.
     *
     * @return array{0: array, 1: array<array{query: string, bindings: array}>}
     */
    protected function listProfilePosts(?int $viewer, int $limit): array
    {
        $db = $this->database();
        $db->enableQueryLog();

        $response = $this->send(
            $this->request('GET', '/api/posts', $viewer ? ['authenticatedAs' => $viewer] : [])
                ->withQueryParams([
                    'filter' => ['author' => 'author', 'type' => 'comment'],
                    'include' => 'user,discussion',
                    'withDefaultIncludes' => '1',
                    'sort' => '-createdAt',
                    'page' => ['limit' => $limit, 'total' => '0'],
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
    protected function discussionStateLookups(array $queries): array
    {
        return array_values(preg_grep(
            '/["`]?(\w+_)?discussion_user["`]?\.["`]?discussion_id["`]? = \?/i',
            array_column($queries, 'query')
        ));
    }

    protected function describeGrowth(array $few, array $many): string
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
