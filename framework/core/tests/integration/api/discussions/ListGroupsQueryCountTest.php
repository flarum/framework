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
 * The discussion list serialises a user for every discussion on the page, so
 * anything loaded per user here is multiplied by the page size.
 *
 * Groups belong in the payload — a user shown without them has no badges, and
 * the frontend keeps that group-less record in its store, so opening that
 * user's profile shows no badges until the page is reloaded. But they have to
 * arrive from the relations already eager-loaded for the whole page rather
 * than a query per user.
 */
class ListGroupsQueryCountTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $discussions = [];
        $posts = [];
        $users = [$this->normalUser()];
        $groupUser = [];

        // Ten discussions, each written by a different user and last replied to
        // by another, so a per-user query would show up as a multiple of the
        // page size rather than a constant.
        for ($i = 1; $i <= 10; $i++) {
            $author = 100 + $i;
            $lastPoster = 200 + $i;

            $discussions[] = [
                'id' => $i,
                'title' => 'Discussion '.$i,
                'created_at' => Carbon::now(),
                'last_posted_at' => Carbon::now(),
                'user_id' => $author,
                'last_posted_user_id' => $lastPoster,
                'first_post_id' => $i,
                'comment_count' => 1,
            ];

            $posts[] = [
                'id' => $i,
                'number' => 1,
                'discussion_id' => $i,
                'created_at' => Carbon::now(),
                'user_id' => $author,
                'type' => 'comment',
                'content' => '<t><p>post '.$i.'</p></t>',
            ];

            foreach ([$author, $lastPoster] as $id) {
                $users[] = [
                    'id' => $id,
                    'username' => 'user'.$id,
                    'email' => 'user'.$id.'@machine.local',
                    'is_email_confirmed' => 1,
                    'password' => 'foobar',
                ];

                $groupUser[] = ['user_id' => $id, 'group_id' => 100];
            }
        }

        $this->prepareDatabase([
            Discussion::class => $discussions,
            Post::class => $posts,
            User::class => $users,
            Group::class => [
                ['id' => 100, 'name_singular' => 'Visible', 'name_plural' => 'Visible', 'is_hidden' => 0],
                ['id' => 101, 'name_singular' => 'Hidden', 'name_plural' => 'Hidden', 'is_hidden' => 1],
            ],
            'group_user' => $groupUser,
        ]);
    }

    /**
     * @return array{count: int, body: array}
     */
    private function listDiscussions(): array
    {
        $db = $this->database();
        $db->flushQueryLog();
        $db->enableQueryLog();

        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $count = 0;
        foreach ($db->getQueryLog() as $query) {
            if (stripos($query['query'], 'group_user') !== false) {
                $count++;
            }
        }

        $db->disableQueryLog();

        return ['count' => $count, 'body' => json_decode($response->getBody()->getContents(), true)];
    }

    #[Test]
    public function listing_discussions_does_not_query_groups_per_user()
    {
        $this->app();

        $result = $this->listDiscussions();

        // 20 users are serialised across the page. A constant handful of
        // queries means the eager-loaded relations are being read; anything
        // that scales with the page is a query per user.
        $this->assertLessThanOrEqual(
            5,
            $result['count'],
            "Listing discussions issued {$result['count']} `group_user` queries for 20 serialised users; that is a query per user rather than a read of the eager-loaded relation."
        );
    }

    #[Test]
    public function listed_users_carry_their_groups()
    {
        $this->app();

        $body = $this->listDiscussions()['body'];

        $users = array_filter($body['included'] ?? [], fn (array $r) => $r['type'] === 'users');

        $this->assertNotEmpty($users, 'No users were included in the discussion list.');

        foreach ($users as $user) {
            $this->assertArrayHasKey(
                'groups',
                $user['relationships'] ?? [],
                "User {$user['id']} was listed without its groups, so it has no badges and poisons the frontend store."
            );
        }

        // The groups themselves have to be in the document, not just named by
        // the relationship, or the frontend has nothing to resolve them to.
        $groups = array_filter($body['included'] ?? [], fn (array $r) => $r['type'] === 'groups');

        $this->assertNotEmpty($groups, 'Groups were referenced but not included in the document.');
    }

    #[Test]
    public function hidden_groups_stay_hidden_in_the_list()
    {
        // Including groups must not become a way to see groups you could not
        // otherwise see.
        $this->app();

        $body = $this->listDiscussions()['body'];

        $groupIds = array_map(
            fn (array $r) => $r['id'],
            array_filter($body['included'] ?? [], fn (array $r) => $r['type'] === 'groups')
        );

        $this->assertNotContains('101', $groupIds, 'A hidden group was exposed in the discussion list.');
    }
}
