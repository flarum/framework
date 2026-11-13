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
use Flarum\Search\SearchManager;
use Flarum\Search\SearchResults;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Covers `POST /api/discussions/read`, the bulk "mark as read" endpoint
 * backed by `ReadDiscussionsHandler`.
 */
class ReadAllTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                // Authored by the default admin (id 1), visible to anyone.
                ['id' => 1, 'title' => 'Admin discussion', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'comment_count' => 1, 'last_post_number' => 5],
                // Authored by the actor (normal, id 2).
                ['id' => 2, 'title' => 'Normal discussion', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 2, 'comment_count' => 1, 'last_post_number' => 7],
                // Authored by admin, but hidden — and the actor has no
                // `discussion.hide` permission, so `ScopeDiscussionVisibility`
                // keeps it out of every search the actor runs.
                ['id' => 3, 'title' => 'Hidden admin discussion', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'hidden_at' => Carbon::now(), 'comment_count' => 1, 'last_post_number' => 3],
                // Authored by the actor, with no posts at all. Visible
                // (self-authored discussions skip the comment-count
                // visibility rule) but has nothing to mark a read-up-to point
                // against.
                ['id' => 4, 'title' => 'Normal discussion with no posts', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 2, 'comment_count' => 0, 'last_post_number' => null],
            ],
        ]);
    }

    #[Test]
    public function a_filtered_request_marks_only_matching_discussions_as_read()
    {
        $response = $this->send(
            $this->request('POST', '/api/discussions/read', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['author' => 'admin']])
        );

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());

        // Matched by the filter, and visible to the actor.
        $this->assertEquals(5, $this->database()->table('discussion_user')->where(['user_id' => 2, 'discussion_id' => 1])->value('last_read_post_number'));

        // Not matched by the filter (different author) — left untouched.
        $this->assertNull($this->database()->table('discussion_user')->where(['user_id' => 2, 'discussion_id' => 2])->first());
        $this->assertNull($this->database()->table('discussion_user')->where(['user_id' => 2, 'discussion_id' => 4])->first());

        $this->assertNull($this->database()->table('users')->find(2)->marked_all_as_read_at);
    }

    #[Test]
    public function a_discussion_the_actor_cannot_see_is_not_marked_read_even_if_it_matches_the_filter()
    {
        // Matches both discussion 1 (visible) and discussion 3 (hidden, same
        // author) by `author`, but discussion 3 is invisible to the actor.
        $response = $this->send(
            $this->request('POST', '/api/discussions/read', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['author' => 'admin']])
        );

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());

        $this->assertNull(
            $this->database()->table('discussion_user')->where(['user_id' => 2, 'discussion_id' => 3])->first(),
            'A discussion the actor cannot see must not be marked as read.'
        );
    }

    #[Test]
    public function a_discussion_with_a_null_last_post_number_is_skipped_without_error()
    {
        // Matches discussion 2 (has posts) and discussion 4 (no posts, null
        // `last_post_number`), both authored by the actor.
        $response = $this->send(
            $this->request('POST', '/api/discussions/read', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['author' => 'normal']])
        );

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());

        $this->assertEquals(7, $this->database()->table('discussion_user')->where(['user_id' => 2, 'discussion_id' => 2])->value('last_read_post_number'));

        $this->assertNull(
            $this->database()->table('discussion_user')->where(['user_id' => 2, 'discussion_id' => 4])->first(),
            'A discussion with no posts has nothing to write a read-up-to row for.'
        );
    }

    #[Test]
    public function an_empty_author_filter_matches_nothing_and_does_not_mark_the_forum_read()
    {
        $response = $this->send(
            $this->request('POST', '/api/discussions/read', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['author' => '']])
        );

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNull($this->database()->table('users')->find(2)->marked_all_as_read_at);
        $this->assertEquals(0, $this->database()->table('discussion_user')->count());
    }

    public static function concurrentReadProvider(): array
    {
        return ['new read row' => [false], 'existing read row' => [true]];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('concurrentReadProvider')]
    public function a_newer_read_between_search_and_upsert_is_preserved(bool $existing)
    {
        $this->app();
        $connection = $this->database();
        $key = ['user_id' => 2, 'discussion_id' => 1];

        if ($existing) {
            $connection->table('discussion_user')->insert($key + ['last_read_post_number' => 2]);
        }

        $readAt = Carbon::now()->addSecond()->toDateTimeString();
        $interleaved = false;
        // Inject the concurrent write after the bulk search, immediately before its INSERT.
        $connection->beforeExecuting(function (string $sql) use ($connection, $key, $readAt, &$interleaved) {
            if (! $interleaved && str_starts_with(strtolower($sql), 'insert into') && str_contains($sql, 'discussion_user')) {
                $interleaved = true;
                $connection->table('discussion_user')->updateOrInsert($key, [
                    'last_read_post_number' => 9,
                    'last_read_at' => $readAt,
                ]);
            }
        });

        $response = $this->send(
            $this->request('POST', '/api/discussions/read', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['author' => 'admin']])
        );

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertTrue($interleaved);
        $row = $connection->table('discussion_user')->where($key)->first();
        $this->assertEquals(9, $row->last_read_post_number);
        $this->assertEquals($readAt, Carbon::parse($row->last_read_at)->toDateTimeString());
    }

    #[Test]
    public function a_search_driver_that_repeats_a_written_page_does_not_loop()
    {
        $this->app();
        $results = new SearchResults(Discussion::whereIn('id', [1, 2])->get(), true, fn () => 1000);
        $search = $this->createMock(SearchManager::class);
        $search->expects($this->exactly(2))->method('query')->willReturn($results);
        $this->app()->getContainer()->instance(SearchManager::class, $search);

        $response = $this->send(
            $this->request('POST', '/api/discussions/read', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['q' => 'search']])
        );

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals(2, $this->database()->table('discussion_user')->where('user_id', 2)->count());
    }

    public static function emptyFilterProvider(): array
    {
        return [
            'no filter at all' => [[]],
            'empty fulltext query' => [['q' => '']],
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('emptyFilterProvider')]
    public function an_unfiltered_request_marks_everything_via_the_timestamp_and_writes_no_rows(array $filter)
    {
        $before = Carbon::now();

        $request = $this->request('POST', '/api/discussions/read', ['authenticatedAs' => 2]);

        if (! empty($filter)) {
            $request = $request->withQueryParams(['filter' => $filter]);
        }

        $response = $this->send($request);

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());

        $markedAllAsReadAt = $this->database()->table('users')->find(2)->marked_all_as_read_at;

        $this->assertNotNull($markedAllAsReadAt);
        $this->assertTrue(Carbon::parse($markedAllAsReadAt)->greaterThanOrEqualTo($before->subSecond()));

        $this->assertEquals(0, $this->database()->table('discussion_user')->where('user_id', 2)->count());
    }

    #[Test]
    public function guests_receive_401()
    {
        // A real guest's browser already holds a CSRF token for their
        // session (issued on every page view), so a bare request with
        // neither cookies nor a token would only prove the CSRF middleware
        // runs before this endpoint's own check — not that the endpoint
        // itself rejects a guest. An initial `GET` stands in for that.
        $initial = $this->send($this->request('GET', '/'));
        $token = $initial->getHeaderLine('X-CSRF-Token');

        $response = $this->send(
            $this->request('POST', '/api/discussions/read', ['cookiesFrom' => $initial])
                ->withHeader('X-CSRF-Token', $token)
                ->withQueryParams(['filter' => ['author' => 'admin']])
        );

        $this->assertEquals(401, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function a_discussion_already_read_in_full_is_not_written_again()
    {
        // Boots the app (and inserts the fixtures from `setUp()`) before the
        // row is seeded, since the database connection is only populated on
        // the first call that needs it.
        $this->app();

        $readAt = Carbon::now()->subYear();

        // Discussion 1 is already read up to its last post, so it no longer
        // matches `unread` and the handler must never touch its row — least of
        // all rewrite `last_read_at` to now.
        $this->database()->table('discussion_user')->insert([
            'user_id' => 2,
            'discussion_id' => 1,
            'last_read_post_number' => 5,
            'last_read_at' => $readAt,
        ]);

        $response = $this->send(
            $this->request('POST', '/api/discussions/read', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['author' => 'admin']])
        );

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());

        $row = $this->database()->table('discussion_user')->where(['user_id' => 2, 'discussion_id' => 1])->first();

        $this->assertEquals(5, $row->last_read_post_number);
        $this->assertEquals(
            $readAt->toDateTimeString(),
            Carbon::parse($row->last_read_at)->toDateTimeString(),
            'A fully-read discussion must not have its read row rewritten.'
        );
    }

    #[Test]
    public function a_match_set_spanning_more_than_one_internal_page_is_marked_read_in_full()
    {
        // One more than `ReadDiscussionsHandler::CHUNK_SIZE`, so the handler
        // must page through the match set at least twice rather than
        // fetching it all in a single query.
        $total = 501;

        $discussions = [];

        for ($i = 1; $i <= $total; $i++) {
            $discussions[] = [
                'id' => 1000 + $i,
                'title' => "Bulk discussion $i",
                'created_at' => Carbon::now(),
                'last_posted_at' => Carbon::now(),
                'user_id' => 3,
                'comment_count' => 1,
                'last_post_number' => $i,
            ];
        }

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'bulk', 'email' => 'bulk@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => $discussions,
        ]);

        $response = $this->send(
            $this->request('POST', '/api/discussions/read', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['author' => 'bulk']])
        );

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());

        $this->assertEquals(
            $total,
            $this->database()->table('discussion_user')->where('user_id', 2)->count(),
            'Every matched discussion across both pages should have a row.'
        );

        // Spot-check the boundary the first internal page ends on, and the
        // one after it, plus the very first and last discussions.
        foreach ([1, 500, 501, $total] as $i) {
            $this->assertEquals(
                $i,
                $this->database()->table('discussion_user')->where(['user_id' => 2, 'discussion_id' => 1000 + $i])->value('last_read_post_number'),
                "Discussion $i should be read up to its own last post number."
            );
        }
    }

    #[Test]
    public function null_last_posts_interleaved_across_pages_do_not_skip_valid_discussions()
    {
        $discussions = [];
        $expected = [];

        for ($i = 0; $i < 1106; $i++) {
            $number = $i % 3 === 0 ? $i + 1 : null;
            $discussions[] = [
                'id' => 1000 + $i,
                'title' => "Mixed discussion $i",
                'created_at' => Carbon::now(),
                'last_posted_at' => Carbon::now(),
                'user_id' => 3,
                'comment_count' => $number === null ? 0 : 1,
                'last_post_number' => $number,
            ];

            if ($number !== null) {
                $expected[1000 + $i] = $number;
            }
        }

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'bulk', 'email' => 'bulk@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => $discussions,
        ]);

        $response = $this->send(
            $this->request('POST', '/api/discussions/read', ['authenticatedAs' => 3])
                ->withQueryParams(['filter' => ['author' => 'bulk']])
        );

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $actual = $this->database()->table('discussion_user')->where('user_id', 3)
            ->pluck('last_read_post_number', 'discussion_id')->all();
        $this->assertEquals($expected, $actual);
    }

    #[Test]
    public function a_lone_unread_filter_marks_everything_via_the_timestamp()
    {
        // `is:unread` singles out every unread discussion, which is exactly
        // the set the unfiltered list marks read — the whole forum. So it
        // takes the cheap path: the one timestamp on the user, no row each.
        $response = $this->send(
            $this->request('POST', '/api/discussions/read', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['unread' => '1']])
        );

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());

        $this->assertNotNull($this->database()->table('users')->find(2)->marked_all_as_read_at);

        $this->assertEquals(
            0,
            $this->database()->table('discussion_user')->where('user_id', 2)->count(),
            'A lone `unread` filter must not write a row per discussion.'
        );
    }

    #[Test]
    public function a_presence_only_filter_still_narrows_the_list()
    {
        // `filter[hidden]=` carries an empty value but is switched on by its
        // presence, so it narrows to hidden discussions and must not be read
        // as "no filter" — which would mark the whole forum read. Run as the
        // admin, who can see hidden discussions.
        $response = $this->send(
            $this->request('POST', '/api/discussions/read', ['authenticatedAs' => 1])
                ->withQueryParams(['filter' => ['hidden' => '']])
        );

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());

        // The hidden discussion matched, so it is read up to its last post.
        $this->assertEquals(
            3,
            $this->database()->table('discussion_user')->where(['user_id' => 1, 'discussion_id' => 3])->value('last_read_post_number')
        );

        // A visible, non-hidden discussion did not match, so it is untouched —
        // proving the empty `hidden` value was kept as a narrowing filter
        // rather than dropped into the mark-everything path.
        $this->assertNull($this->database()->table('discussion_user')->where(['user_id' => 1, 'discussion_id' => 1])->first());
        $this->assertNull($this->database()->table('users')->find(1)->marked_all_as_read_at);
    }
}
