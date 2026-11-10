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
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Regression test for the N+1 `group_user` queries described in
 * https://github.com/flarum/framework/issues/4695.
 *
 * The posts index includes `user.groups`. Before the fix, the UserResource
 * `groups` getter issued a fresh `group_user` query for every serialized post
 * author, so the query count grew linearly with the number of distinct authors.
 * After the fix the relation is eager-loaded once for the whole payload.
 */
class ListGroupsQueryCountTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * Number of distinct post authors. Deliberately large so that an N+1 would
     * produce many more `group_user` queries than the single batched load.
     */
    private const AUTHOR_COUNT = 8;

    protected function setUp(): void
    {
        parent::setUp();

        $users = [];
        $posts = [];
        $groupUser = [];

        for ($i = 0; $i < self::AUTHOR_COUNT; $i++) {
            $userId = 10 + $i;
            $users[] = [
                'id' => $userId,
                'username' => 'author'.$userId,
                'email' => 'author'.$userId.'@machine.local',
                'is_email_confirmed' => 1,
                'password' => 'foobar',
            ];
            $posts[] = [
                'id' => 100 + $i,
                'number' => $i + 1,
                'discussion_id' => 1,
                'created_at' => Carbon::now(),
                'user_id' => $userId,
                'type' => 'comment',
                'content' => '<t><p>post by '.$userId.'</p></t>',
            ];
            // Put every author in both a visible and a hidden group, so we also
            // exercise hidden-group filtering across many distinct users.
            $groupUser[] = ['user_id' => $userId, 'group_id' => 100];
            $groupUser[] = ['user_id' => $userId, 'group_id' => 101];
        }

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 10, 'first_post_id' => 100, 'comment_count' => self::AUTHOR_COUNT],
            ],
            Post::class => $posts,
            User::class => array_merge([$this->normalUser()], $users),
            Group::class => [
                ['id' => 100, 'name_singular' => 'Visible', 'name_plural' => 'Visible', 'is_hidden' => 0],
                ['id' => 101, 'name_singular' => 'Hidden', 'name_plural' => 'Hidden', 'is_hidden' => 1],
            ],
            'group_user' => $groupUser,
        ]);
    }

    private function listPostsForDiscussion(): array
    {
        // Authenticate as the normal user (id 2), a non-admin who cannot view
        // hidden groups – this exercises both the groups relationship getter and
        // the email-field `editCredentials`/`isAdmin` path for every author.
        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['discussion' => 1]])
        );

        $this->assertEquals(200, $response->getStatusCode());

        return json_decode($response->getBody()->getContents(), true);
    }

    #[Test]
    public function groups_are_batch_loaded_without_an_n_plus_one()
    {
        // Boot the app and populate the database before we start counting.
        $this->app();

        $db = $this->database();
        $db->flushQueryLog();
        $db->enableQueryLog();

        $this->listPostsForDiscussion();

        // Classify every `group_user` query. The post authors' groups must be
        // loaded in one batched `where user_id in (...)` query – never one query
        // per author (which was the N+1). Loading the actor's own groups for
        // permission checks is constant overhead and unrelated.
        $batchedAuthorLoads = 0;
        $individualAuthorLoads = 0;

        foreach ($db->getQueryLog() as $query) {
            if (stripos($query['query'], 'group_user') === false) {
                continue;
            }

            if (stripos($query['query'], ' in (') !== false) {
                $batchedAuthorLoads++;
                continue;
            }

            // A single-row `where user_id = ?` load – flag it if it targets one
            // of the post authors (ids >= 10) rather than the actor.
            foreach ($query['bindings'] as $binding) {
                if ((int) $binding >= 10) {
                    $individualAuthorLoads++;
                }
            }
        }

        $db->disableQueryLog();

        $this->assertSame(
            0,
            $individualAuthorLoads,
            "Post authors' groups were loaded individually ($individualAuthorLoads times) instead of in a single batched query – this is the N+1 from issue #4695."
        );
        $this->assertGreaterThanOrEqual(
            1,
            $batchedAuthorLoads,
            'Expected the authors\' groups to be eager-loaded in a single batched query.'
        );
    }

    #[Test]
    public function hidden_groups_are_still_filtered_for_many_users()
    {
        $body = $this->listPostsForDiscussion();

        $groupIds = array_values(array_unique(array_map(
            fn (array $resource) => $resource['id'],
            array_filter($body['included'] ?? [], fn (array $r) => ($r['type'] ?? null) === 'groups')
        )));

        // Group 101 is hidden and the actor (normal user, id 1) cannot view it.
        $this->assertContains('100', $groupIds);
        $this->assertNotContains('101', $groupIds);
    }
}
