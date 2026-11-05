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
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Database\Schema\Blueprint;
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

    public static function emptyFilterProvider(): array
    {
        return [
            'no filter at all' => [[]],
            'filter present but every value empty' => [['q' => '', 'author' => '']],
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
    public function an_existing_row_keeps_its_other_columns_after_the_upsert()
    {
        // Boots the app (and inserts the fixtures from `setUp()`) before the
        // schema is touched directly: `populateDatabase()` runs on the first
        // call that needs the database connection, so the extra column has
        // to go on after that, not before.
        $this->app();

        $this->database()->getSchemaBuilder()->table('discussion_user', function (Blueprint $table) {
            $table->string('extension_column')->nullable();
        });

        $this->database()->table('discussion_user')->insert([
            'user_id' => 2,
            'discussion_id' => 1,
            'last_read_post_number' => 1,
            'last_read_at' => Carbon::now()->subDay(),
            'extension_column' => 'keep-me',
        ]);

        $response = $this->send(
            $this->request('POST', '/api/discussions/read', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['author' => 'admin']])
        );

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());

        $row = $this->database()->table('discussion_user')->where(['user_id' => 2, 'discussion_id' => 1])->first();

        // Updated: the read columns the upsert is responsible for.
        $this->assertEquals(5, $row->last_read_post_number);

        // Untouched: a column the upsert was never told about, standing in
        // for one an extension (e.g. flarum/subscriptions) keeps here.
        $this->assertEquals('keep-me', $row->extension_column);
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
}
