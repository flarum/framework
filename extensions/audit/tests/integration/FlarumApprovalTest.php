<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

use Carbon\Carbon;

class FlarumApprovalTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-approval', 'flarum-flags');

        $date = Carbon::parse('2021-01-01T12:00:00+00:00');

        $this->prepareDatabase([
            'discussions' => [
                ['id' => 1, 'title' => 'A', 'created_at' => $date, 'is_approved' => false, 'last_posted_at' => $date, 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            'posts' => [
                ['id' => 2, 'number' => 1, 'discussion_id' => 1, 'created_at' => $date, 'is_approved' => false, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>A</p></t>'],
            ],
        ]);
    }

    /**
     * @test
     */
    public function approve()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/posts/2', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'isApproved' => true,
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('post.approved', [
            'discussion_id' => 1,
            'post_id' => 2,
        ]);
    }
}
