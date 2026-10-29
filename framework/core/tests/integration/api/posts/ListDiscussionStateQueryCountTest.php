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
 * Regression test for the N+1 `discussion_user` (UserState) queries described in
 * https://github.com/flarum/framework/issues/4769.
 *
 * The posts index includes `discussion`, and DiscussionResource serializes the
 * per-actor `state` (its `lastReadAt` / `lastReadPostNumber` attributes read
 * `$discussion->state`). Before the fix that relation was lazy-loaded once per
 * distinct discussion, so the query count grew linearly with the number of
 * discussions in the payload. After the fix it is eager-loaded in a single
 * batched query.
 */
class ListDiscussionStateQueryCountTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * Number of distinct discussions represented in the posts payload.
     * Deliberately large so an N+1 produces many more `discussion_user` queries
     * than the single batched load.
     */
    private const DISCUSSION_COUNT = 8;

    protected function setUp(): void
    {
        parent::setUp();

        $discussions = [];
        $posts = [];
        $discussionUser = [];

        for ($i = 0; $i < self::DISCUSSION_COUNT; $i++) {
            $discussionId = $i + 1;
            $postId = 100 + $i;

            $discussions[] = [
                'id' => $discussionId,
                'title' => __CLASS__.' '.$discussionId,
                'created_at' => Carbon::now(),
                'last_posted_at' => Carbon::now(),
                'user_id' => 2,
                'first_post_id' => $postId,
                'comment_count' => 1,
            ];
            $posts[] = [
                'id' => $postId,
                'number' => 1,
                'discussion_id' => $discussionId,
                'created_at' => Carbon::now(),
                'user_id' => 2,
                'type' => 'comment',
                'content' => '<t><p>post in '.$discussionId.'</p></t>',
            ];
            // A UserState row per discussion for the actor, so `state` is
            // non-null and its attributes are serialized.
            $discussionUser[] = [
                'user_id' => 2,
                'discussion_id' => $discussionId,
                'last_read_post_number' => 1,
            ];
        }

        $this->prepareDatabase([
            Discussion::class => $discussions,
            Post::class => $posts,
            User::class => [$this->normalUser()],
            'discussion_user' => $discussionUser,
        ]);
    }

    private function listPosts(): array
    {
        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 2])
        );

        $this->assertEquals(200, $response->getStatusCode());

        return json_decode($response->getBody()->getContents(), true);
    }

    #[Test]
    public function discussion_state_is_batch_loaded_without_an_n_plus_one()
    {
        // Boot the app and populate the database before we start counting.
        $this->app();

        $db = $this->database();
        $db->flushQueryLog();
        $db->enableQueryLog();

        $this->listPosts();

        // The included discussions' per-actor `state` must be loaded in a single
        // batched `where discussion_id in (...)` query — never one query per
        // discussion (which was the N+1).
        $batchedLoads = 0;
        $individualLoads = 0;

        foreach ($db->getQueryLog() as $query) {
            if (stripos($query['query'], 'discussion_user') === false) {
                continue;
            }

            if (stripos($query['query'], ' in (') !== false) {
                $batchedLoads++;
            } else {
                $individualLoads++;
            }
        }

        $db->disableQueryLog();

        $this->assertSame(
            0,
            $individualLoads,
            "Discussion states were loaded individually ($individualLoads times) instead of in a single batched query — this is the N+1 from issue #4769."
        );
        $this->assertGreaterThanOrEqual(
            1,
            $batchedLoads,
            'Expected the discussion states to be eager-loaded in a single batched query.'
        );
    }
}
