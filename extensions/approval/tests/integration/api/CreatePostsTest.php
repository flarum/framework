<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Approval\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Approval\Tests\integration\InteractsWithUnapprovedContent;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class CreatePostsTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use InteractsWithUnapprovedContent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-flags', 'flarum-approval');

        $this->prepareDatabase([
            User::class => [
                ['id' => 1, 'username' => 'Muralf', 'email' => 'muralf@machine.local', 'is_email_confirmed' => 1],
                $this->normalUser(),
                ['id' => 3, 'username' => 'acme', 'email' => 'acme@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'luceos', 'email' => 'luceos@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 4, 'first_post_id' => 1, 'comment_count' => 1, 'is_approved' => 1],
                ['id' => 2, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 4, 'first_post_id' => 2, 'comment_count' => 1, 'is_approved' => 0],
                ['id' => 3, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 4, 'first_post_id' => 3, 'comment_count' => 1, 'is_approved' => 0],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'is_approved' => 1, 'number' => 1],
                ['id' => 2, 'discussion_id' => 1, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'is_approved' => 1, 'number' => 2],
                ['id' => 3, 'discussion_id' => 1, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'is_approved' => 1, 'number' => 3],
                ['id' => 4, 'discussion_id' => 2, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'is_approved' => 1, 'number' => 1],
                ['id' => 5, 'discussion_id' => 2, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'is_approved' => 1, 'number' => 2],
                ['id' => 6, 'discussion_id' => 2, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'is_approved' => 1, 'number' => 3],
                ['id' => 7, 'discussion_id' => 3, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'is_approved' => 1, 'number' => 1],
                ['id' => 8, 'discussion_id' => 3, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'is_approved' => 1, 'number' => 2],
                ['id' => 9, 'discussion_id' => 3, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'is_approved' => 0, 'number' => 3],
            ],
            Group::class => [
                ['id' => 4, 'name_singular' => 'Acme', 'name_plural' => 'Acme', 'is_hidden' => 0],
                ['id' => 5, 'name_singular' => 'Acme', 'name_plural' => 'Acme', 'is_hidden' => 0],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 4],
                ['user_id' => 2, 'group_id' => 5],
            ],
            'group_permission' => [
                ['group_id' => 4, 'permission' => 'discussion.startWithoutApproval'],
                ['group_id' => 5, 'permission' => 'discussion.replyWithoutApproval'],
                ['group_id' => Group::MEMBER_ID, 'permission' => 'postWithoutThrottle'],
            ]
        ]);
    }

    /**
     * @dataProvider startDiscussionDataProvider
     * @test
     */
    public function can_start_discussion_without_approval_when_allowed(int $authenticatedAs, bool $allowed)
    {
        $this->database()->table('group_permission')->where('group_id', Group::MEMBER_ID)->where('permission', 'discussion.startWithoutApproval')->delete();

        $response = $this->send(
            $this->request('POST', '/api/discussions', [
                'authenticatedAs' => $authenticatedAs,
                'json' => [
                    'data' => [
                        'type' => 'discussions',
                        'attributes' => [
                            'title' => 'This is a new discussion',
                            'content' => 'This is a new discussion',
                        ]
                    ]
                ]
            ])
        );

        $body = $response->getBody()->getContents();
        $json = json_decode($body, true);

        $this->assertEquals(201, $response->getStatusCode(), $body);
        $this->assertEquals($allowed ? 1 : 0, $this->database()->table('discussions')->where('id', $json['data']['id'])->value('is_approved'));
    }

    /**
     * @dataProvider replyToDiscussionDataProvider
     * @test
     */
    public function can_reply_without_approval_when_allowed(?int $authenticatedAs, bool $allowed)
    {
        $this->database()->table('group_permission')->where('group_id', Group::MEMBER_ID)->where('permission', 'discussion.replyWithoutApproval')->delete();

        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => $authenticatedAs,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => 'This is a new reply',
                        ],
                        'relationships' => [
                            'discussion' => [
                                'data' => [
                                    'type' => 'discussions',
                                    'id' => 1
                                ]
                            ]
                        ]
                    ]
                ]
            ])
        );

        $body = $response->getBody()->getContents();
        $json = json_decode($body, true);

        $this->assertEquals(201, $response->getStatusCode(), $body);
        $this->assertEquals($allowed ? 1 : 0, $this->database()->table('posts')->where('id', $json['data']['id'])->value('is_approved'));
    }

    public static function startDiscussionDataProvider(): array
    {
        return [
            'Admin' => [1, true],
            'User without permission' => [2, false],
            'Permission Given' => [3, true],
            'Another user without permission' => [4, false],
        ];
    }

    public static function replyToDiscussionDataProvider(): array
    {
        return [
            'Admin' => [1, true],
            'User without permission' => [3, false],
            'Permission Given' => [2, true],
            'Another user without permission' => [4, false],
        ];
    }
}
