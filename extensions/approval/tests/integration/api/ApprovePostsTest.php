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
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

class ApprovePostsTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use InteractsWithUnapprovedContent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-approval');

        $this->prepareDatabase([
            'users' => [
                ['id' => 1, 'username' => 'Muralf', 'email' => 'muralf@machine.local', 'is_email_confirmed' => 1],
                $this->normalUser(),
                ['id' => 3, 'username' => 'acme', 'email' => 'acme@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'luceos', 'email' => 'luceos@machine.local', 'is_email_confirmed' => 1],
            ],
            'discussions' => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 4, 'first_post_id' => 1, 'comment_count' => 1, 'is_approved' => 1],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'hidden_at' => 0, 'is_approved' => 1, 'number' => 1],
                ['id' => 2, 'discussion_id' => 1, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'hidden_at' => 0, 'is_approved' => 1, 'number' => 2],
                ['id' => 3, 'discussion_id' => 1, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'hidden_at' => 0, 'is_approved' => 0, 'number' => 3],
                ['id' => 4, 'discussion_id' => 1, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'hidden_at' => Carbon::now(), 'is_approved' => 1, 'number' => 4],
                ['id' => 5, 'discussion_id' => 1, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'hidden_at' => 0, 'is_approved' => 0, 'number' => 5],
            ],
            'groups' => [
                ['id' => 4, 'name_singular' => 'Acme', 'name_plural' => 'Acme', 'is_hidden' => 0],
                ['id' => 5, 'name_singular' => 'Acme', 'name_plural' => 'Acme', 'is_hidden' => 0],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 4],
            ],
            'group_permission' => [
                ['group_id' => 4, 'permission' => 'discussion.approvePosts'],
            ]
        ]);
    }

    /**
     * @test
     */
    public function can_approve_unapproved_post()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/3', [
                'authenticatedAs' => 3,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'isApproved' => true
                        ]
                    ]
                ]
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());
        $this->assertEquals(1, $this->database()->table('posts')->where('id', 3)->where('is_approved', 1)->count());
    }

    /**
     * @test
     */
    public function cannot_approve_post_without_permission()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/3', [
                'authenticatedAs' => 4,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'isApproved' => true
                        ]
                    ]
                ]
            ])
        );

        $this->assertEquals(403, $response->getStatusCode(), $response->getBody()->getContents());
        $this->assertEquals(0, $this->database()->table('posts')->where('id', 3)->where('is_approved', 1)->count());
    }

    /**
     * @test
     */
    public function hiding_post_silently_approves_it()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/5', [
                'authenticatedAs' => 3,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'isHidden' => true
                        ]
                    ]
                ]
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());
        $this->assertEquals(1, $this->database()->table('posts')->where('id', 5)->where('is_approved', 1)->count());
    }
}
