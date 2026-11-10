<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Approval\Tests\integration;

use Carbon\Carbon;
use Flarum\Audit\Tests\integration\InteractsWithAuditLog;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuditTest extends TestCase
{
    use InteractsWithAuditLog;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpAuditLog();

        $this->extension('flarum-audit', 'flarum-flags', 'flarum-approval');

        $date = Carbon::parse('2021-01-01T12:00:00+00:00');

        $this->prepareDatabase([
            Discussion::class => [
                // first_post_id must reference the seeded post (id 2); PostgreSQL enforces the
                // discussions_first_post_id_foreign FK, unlike MySQL/SQLite in the test config.
                // Discussion 1 / post 2: unapproved first post (used for approval).
                ['id' => 1, 'title' => 'A', 'created_at' => $date, 'is_approved' => false, 'last_posted_at' => $date, 'user_id' => 1, 'first_post_id' => 2, 'last_post_id' => 2, 'last_post_number' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 2, 'number' => 1, 'discussion_id' => 1, 'created_at' => $date, 'is_approved' => false, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>A</p></t>'],
            ],
        ]);
    }

    #[Test]
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

    #[Test]
    public function approving_the_first_post_logs_the_discussion_approval()
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

        $this->assertLogExists('discussion.approved', [
            'discussion_id' => 1,
        ]);
    }
}
