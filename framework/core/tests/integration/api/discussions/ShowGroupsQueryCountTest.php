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
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Regression test for part (B) of https://github.com/flarum/framework/issues/4695:
 * DiscussionResource::Show did not eager-load groups for the `user` and
 * `lastPostedUser` it serialises, so the `email` field's
 * `editCredentials`/`isAdmin()` check lazy-loaded each user's groups in its own
 * `group_user` query.
 */
class ShowGroupsQueryCountTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 20, 'last_posted_user_id' => 21, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 22, 'type' => 'comment', 'content' => '<t><p>first post</p></t>'],
            ],
            User::class => [
                $this->normalUser(),
                ['id' => 20, 'username' => 'author', 'email' => 'author@machine.local', 'is_email_confirmed' => 1, 'password' => 'foobar'],
                ['id' => 21, 'username' => 'lastposter', 'email' => 'lastposter@machine.local', 'is_email_confirmed' => 1, 'password' => 'foobar'],
                ['id' => 22, 'username' => 'firstposter', 'email' => 'firstposter@machine.local', 'is_email_confirmed' => 1, 'password' => 'foobar'],
            ],
            Group::class => [
                ['id' => 100, 'name_singular' => 'Visible', 'name_plural' => 'Visible', 'is_hidden' => 0],
            ],
            'group_user' => [
                ['user_id' => 20, 'group_id' => 100],
                ['user_id' => 21, 'group_id' => 100],
                ['user_id' => 22, 'group_id' => 100],
            ],
        ]);
    }

    private function countGroupUserQueries(): int
    {
        $db = $this->database();
        $db->flushQueryLog();
        $db->enableQueryLog();

        // Authenticate as the normal user (id 2): not the author, last poster or
        // first poster, so the email field's editCredentials/isAdmin check runs
        // against each of those users.
        $response = $this->send(
            $this->request('GET', '/api/discussions/1', ['authenticatedAs' => 2])
        );

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());

        $count = 0;
        foreach ($db->getQueryLog() as $query) {
            if (stripos($query['query'], 'group_user') !== false) {
                $count++;
            }
        }

        $db->disableQueryLog();

        return $count;
    }

    #[Test]
    public function user_groups_are_eager_loaded_on_discussion_show()
    {
        $this->app();

        $count = $this->countGroupUserQueries();

        // The discussion serialises three distinct users with groups (author,
        // last poster, first-post author) plus the actor, so each user's groups
        // are loaded exactly once: 3 + 1 = 4. Previously firstPost.user's groups
        // were loaded twice – once by the relationship getter (visibleGroups) and
        // again by the email field's isAdmin() check (groups) – for 5 queries.
        $this->assertLessThanOrEqual(
            4,
            $count,
            "Discussion show issued $count `group_user` queries; expected at most 4. The extra query is the redundant per-user groups load from issue #4695."
        );
    }
}
