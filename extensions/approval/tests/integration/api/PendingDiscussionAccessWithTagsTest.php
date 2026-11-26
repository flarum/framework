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
use Flarum\Tags\Tag;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * @see https://github.com/flarum/framework/issues/3594
 */
class PendingDiscussionAccessWithTagsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'flarum-flags', 'flarum-approval');

        $pending = fn (int $id) => ['id' => $id, 'title' => "Pending $id", 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => $id, 'last_post_id' => $id, 'last_post_number' => 1, 'comment_count' => 0, 'is_approved' => 0, 'is_private' => 1];
        $pendingPost = fn (int $id) => ['id' => $id, 'discussion_id' => $id, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Pending</p></t>', 'number' => 1, 'is_approved' => 0, 'is_private' => 1];

        $this->prepareDatabase([
            Tag::class => [
                ['id' => 1, 'name' => 'Approvable', 'slug' => 'approvable', 'position' => 0, 'is_restricted' => true],
                ['id' => 2, 'name' => 'Flags only', 'slug' => 'flags-only', 'position' => 1, 'is_restricted' => true],
                ['id' => 3, 'name' => 'Open', 'slug' => 'open', 'position' => 2, 'is_restricted' => false],
            ],
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'tagapprover', 'email' => 'tagapprover@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'approver', 'email' => 'approver@machine.local', 'is_email_confirmed' => 1],
                ['id' => 5, 'username' => 'member', 'email' => 'member@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [$pending(1), $pending(2), $pending(3)],
            Post::class => [$pendingPost(1), $pendingPost(2), $pendingPost(3)],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
                ['discussion_id' => 2, 'tag_id' => 2],
                ['discussion_id' => 3, 'tag_id' => 3],
            ],
            Flag::class => [
                ['id' => 1, 'post_id' => 1, 'type' => 'approval', 'user_id' => null, 'created_at' => Carbon::now()],
                ['id' => 2, 'post_id' => 2, 'type' => 'approval', 'user_id' => null, 'created_at' => Carbon::now()],
                ['id' => 3, 'post_id' => 3, 'type' => 'approval', 'user_id' => null, 'created_at' => Carbon::now()],
            ],
            Group::class => [
                ['id' => 10, 'name_singular' => 'Tag approver', 'name_plural' => 'Tag approvers', 'is_hidden' => 0],
                ['id' => 11, 'name_singular' => 'Approver', 'name_plural' => 'Approvers', 'is_hidden' => 0],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 10],
                ['user_id' => 4, 'group_id' => 11],
            ],
            'group_permission' => [
                ['group_id' => 10, 'permission' => 'tag1.viewForum'],
                ['group_id' => 10, 'permission' => 'tag1.discussion.viewFlags'],
                ['group_id' => 10, 'permission' => 'tag1.discussion.approvePosts'],
                ['group_id' => 10, 'permission' => 'tag2.viewForum'],
                ['group_id' => 10, 'permission' => 'tag2.discussion.viewFlags'],
                ['group_id' => 11, 'permission' => 'tag1.viewForum'],
                ['group_id' => 11, 'permission' => 'tag2.viewForum'],
                ['group_id' => 11, 'permission' => 'discussion.viewFlags'],
                ['group_id' => 11, 'permission' => 'discussion.approvePosts'],
            ],
        ]);
    }

    public static function visibility(): array
    {
        return [
            'tag approver, tag where they may approve' => [3, 1, true],
            'tag approver, tag where they may only view flags' => [3, 2, false],
            'tag approver, unrestricted tag' => [3, 3, false],
            'global approver, unrestricted tag' => [4, 3, true],
            'member' => [5, 3, false],
            'admin' => [1, 2, true],
        ];
    }

    #[Test]
    #[DataProvider('visibility')]
    public function pending_discussion_is_visible_only_where_the_user_may_approve(int $actorId, int $discussionId, bool $canSee)
    {
        $response = $this->send(
            $this->request('GET', "/api/discussions/$discussionId", ['authenticatedAs' => $actorId])
        );

        $this->assertEquals($canSee ? 200 : 404, $response->getStatusCode(), $response->getBody()->getContents());

        $response = $this->send(
            $this->request('GET', "/api/posts/$discussionId", ['authenticatedAs' => $actorId])
        );

        $this->assertEquals($canSee ? 200 : 404, $response->getStatusCode(), $response->getBody()->getContents());
    }

    #[Test]
    public function tag_approver_can_approve_only_in_the_tag_where_they_may_approve()
    {
        $approve = fn (int $postId) => $this->send(
            $this->request('PATCH', "/api/posts/$postId", [
                'authenticatedAs' => 3,
                'json' => ['data' => ['attributes' => ['isApproved' => true]]],
            ])
        );

        $response = $approve(1);
        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());
        $this->assertEquals(1, $this->database()->table('discussions')->where('id', 1)->value('is_approved'));

        $this->assertEquals(404, $approve(2)->getStatusCode());
        $this->assertEquals(0, $this->database()->table('discussions')->where('id', 2)->value('is_approved'));
    }

    #[Test]
    public function tag_approver_sees_approval_flags_only_for_discussions_they_can_see()
    {
        $response = $this->send(
            $this->request('GET', '/api/flags', ['authenticatedAs' => 3])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEqualsCanonicalizing(['1'], Arr::pluck(json_decode($response->getBody()->getContents(), true)['data'], 'id'));
    }
}
