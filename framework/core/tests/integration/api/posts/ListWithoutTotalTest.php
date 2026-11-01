<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\posts;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Post lists don't show a total, and counting a prolific author's posts can
 * cost more than the page itself, so they send `page[total]=0`: the posts
 * endpoint must then not count. Its results come from the searcher, which
 * defers the count, unlike a plain query.
 */
class ListWithoutTotalTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private const POSTS = 3;

    protected function setUp(): void
    {
        parent::setUp();

        $discussions = [];
        $posts = [];

        for ($i = 1; $i <= self::POSTS; $i++) {
            $discussions[] = ['id' => $i, 'title' => "Discussion $i", 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => $i, 'comment_count' => 2, 'last_post_number' => 2];
            $posts[] = ['id' => $i, 'discussion_id' => $i, 'number' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Opening post</p></t>'];
            $posts[] = ['id' => 100 + $i, 'discussion_id' => $i, 'number' => 2, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Reply</p></t>'];
        }

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => $discussions,
            Post::class => $posts,
        ]);
    }

    #[Test]
    public function a_profile_list_without_a_total_runs_no_count(): void
    {
        [$document, $counts] = $this->listProfilePosts(['limit' => 2, 'total' => '0']);

        $this->assertSame([], $counts, 'The posts were counted although the request asked for no total.');
        $this->assertArrayNotHasKey('total', $document['meta']['page'] ?? []);
        $this->assertCount(2, $document['data']);
        $this->assertArrayHasKey('next', $document['links'] ?? [], 'A full page still links to the next one.');
    }

    #[Test]
    public function a_last_page_that_happens_to_be_full_links_to_no_next_one(): void
    {
        // Without a total, a full page used to be taken to mean there are
        // more: a user with exactly a page of posts was offered one more page,
        // which was empty. The searcher knows, having fetched one past the page.
        [$document, $counts] = $this->listProfilePosts(['limit' => self::POSTS, 'total' => '0']);

        $this->assertSame([], $counts);
        $this->assertCount(self::POSTS, $document['data']);
        $this->assertArrayNotHasKey('next', $document['links'] ?? [], 'The last page linked to a next one, which is empty.');
    }

    #[Test]
    public function a_profile_list_counts_by_default(): void
    {
        // Proves the count query is recognised, so the test above can't pass
        // only because nothing matched.
        [$document, $counts] = $this->listProfilePosts(['limit' => 2]);

        $this->assertCount(1, $counts);
        $this->assertSame(self::POSTS, $document['meta']['page']['total'] ?? null);
    }

    /**
     * The request PostsUserPage's PostListState sends.
     *
     * @return array{0: array, 1: string[]} the document, and the count queries run against posts
     */
    private function listProfilePosts(array $page): array
    {
        $db = $this->database();
        $db->enableQueryLog();

        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 2])
                ->withQueryParams([
                    'filter' => ['author' => 'normal', 'type' => 'comment'],
                    'include' => 'user,discussion',
                    'withDefaultIncludes' => '1',
                    'sort' => '-createdAt',
                    'page' => $page,
                ])
        );

        $queries = array_column($db->getQueryLog(), 'query');
        $db->flushQueryLog();

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        // Identifier quoting differs between drivers (backticks on MySQL and
        // MariaDB, double quotes on PostgreSQL and SQLite), and the table name
        // carries the connection's prefix.
        $posts = $db->getTablePrefix().'posts';

        $counts = array_values(array_filter(
            $queries,
            fn (string $sql) => preg_match("/count\(\*\) as aggregate from $posts\b/i", str_replace(['`', '"'], '', $sql)) === 1
        ));

        return [json_decode($body, true), $counts];
    }
}
