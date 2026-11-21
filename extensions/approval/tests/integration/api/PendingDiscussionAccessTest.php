<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Approval\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Flags\Flag;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * A discussion awaiting approval is private and, because its only post is
 * private too, has a comment_count of 0.
 *
 * @see https://github.com/flarum/framework/issues/3594
 */
class PendingDiscussionAccessTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-flags', 'flarum-approval');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'approver', 'email' => 'approver@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'author', 'email' => 'author@machine.local', 'is_email_confirmed' => 1],
                ['id' => 5, 'username' => 'flagviewer', 'email' => 'flagviewer@machine.local', 'is_email_confirmed' => 1],
                ['id' => 6, 'username' => 'moderator', 'email' => 'moderator@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Pending', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 4, 'first_post_id' => 1, 'last_post_id' => 1, 'last_post_number' => 1, 'comment_count' => 0, 'is_approved' => 0, 'is_private' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Pending</p></t>', 'number' => 1, 'is_approved' => 0, 'is_private' => 1],
            ],
            Flag::class => [
                ['id' => 1, 'post_id' => 1, 'type' => 'approval', 'user_id' => null, 'created_at' => Carbon::now()],
            ],
            Group::class => [
                ['id' => 10, 'name_singular' => 'Approver', 'name_plural' => 'Approvers', 'is_hidden' => 0],
                ['id' => 11, 'name_singular' => 'Flag viewer', 'name_plural' => 'Flag viewers', 'is_hidden' => 0],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 10],
                ['user_id' => 5, 'group_id' => 11],
                ['user_id' => 6, 'group_id' => Group::MODERATOR_ID],
            ],
            'group_permission' => [
                ['group_id' => 10, 'permission' => 'discussion.viewFlags'],
                ['group_id' => 10, 'permission' => 'discussion.approvePosts'],
                ['group_id' => 11, 'permission' => 'discussion.viewFlags'],
            ],
        ]);
    }

    public static function actors(): array
    {
        return [
            'guest' => [null, false],
            'member' => [2, false],
            'author' => [4, true],
            'group with view flags only' => [5, false],
            'group with view flags and approve posts' => [3, true],
            'moderator' => [6, true],
            'admin' => [1, true],
        ];
    }

    #[Test]
    #[DataProvider('actors')]
    public function only_users_who_may_approve_or_authored_it_can_see_the_pending_discussion(?int $actorId, bool $canSee)
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions/1', ['authenticatedAs' => $actorId])
        );

        $this->assertEquals($canSee ? 200 : 404, $response->getStatusCode(), $response->getBody()->getContents());
    }

    #[Test]
    #[DataProvider('actors')]
    public function only_users_who_may_approve_or_authored_it_see_the_pending_discussion_listed(?int $actorId, bool $canSee)
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => $actorId])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $ids = Arr::pluck(json_decode($response->getBody()->getContents(), true)['data'], 'id');

        $canSee ? $this->assertContains('1', $ids) : $this->assertNotContains('1', $ids);
    }

    #[Test]
    #[DataProvider('actors')]
    public function only_users_who_may_approve_or_authored_it_can_see_the_pending_post(?int $actorId, bool $canSee)
    {
        $response = $this->send(
            $this->request('GET', '/api/posts/1', ['authenticatedAs' => $actorId])
        );

        $this->assertEquals($canSee ? 200 : 404, $response->getStatusCode(), $response->getBody()->getContents());
    }

    #[Test]
    public function user_who_may_approve_but_not_edit_posts_can_approve_the_pending_discussion()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/1', [
                'authenticatedAs' => 3,
                'json' => ['data' => ['attributes' => ['isApproved' => true]]],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());

        $discussion = $this->database()->table('discussions')->find(1);
        $this->assertEquals(1, $discussion->is_approved);
        $this->assertEquals(0, $discussion->is_private);
        $this->assertEquals(1, $discussion->comment_count);
    }

    #[Test]
    public function user_with_view_flags_only_cannot_approve_the_pending_discussion()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/1', [
                'authenticatedAs' => 5,
                'json' => ['data' => ['attributes' => ['isApproved' => true]]],
            ])
        );

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals(0, $this->database()->table('posts')->where('id', 1)->value('is_approved'));
    }

    public static function flagViewers(): array
    {
        return [
            'group with view flags only' => [5, false],
            'group with view flags and approve posts' => [3, true],
            'moderator' => [6, true],
            'admin' => [1, true],
        ];
    }

    #[Test]
    #[DataProvider('flagViewers')]
    public function approval_flag_is_listed_only_for_users_who_can_see_the_pending_discussion(int $actorId, bool $canSee)
    {
        $response = $this->send(
            $this->request('GET', '/api/flags', ['authenticatedAs' => $actorId])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $ids = Arr::pluck(json_decode($response->getBody()->getContents(), true)['data'], 'id');

        $canSee ? $this->assertContains('1', $ids) : $this->assertNotContains('1', $ids);
    }

    #[Test]
    public function discussion_started_without_approval_permission_is_visible_to_user_who_may_approve()
    {
        $this->database()->table('group_permission')->where('group_id', Group::MEMBER_ID)->where('permission', 'discussion.startWithoutApproval')->delete();

        $response = $this->send(
            $this->request('POST', '/api/discussions', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'discussions',
                        'attributes' => [
                            'title' => 'Needs approval',
                            'content' => 'Needs approval',
                        ],
                    ],
                ],
            ])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(201, $response->getStatusCode(), $body);

        $id = json_decode($body, true)['data']['id'];

        $discussion = $this->database()->table('discussions')->find($id);
        $this->assertEquals(0, $discussion->is_approved);
        $this->assertEquals(1, $discussion->is_private);
        $this->assertEquals(0, $discussion->comment_count);

        $response = $this->send(
            $this->request('GET', "/api/discussions/$id", ['authenticatedAs' => 3])
        );

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());
    }
}
