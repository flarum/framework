<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Tests\integration;

use Carbon\Carbon;
use Flarum\Audit\Tests\integration\InteractsWithAuditLog;
use Flarum\Discussion\Discussion;
use Flarum\Flags\Flag;
use Flarum\Post\Post;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class AuditTest extends TestCase
{
    use InteractsWithAuditLog;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpAuditLog();

        $this->extension('flarum-audit', 'flarum-flags');

        $this->setting('flarum-flags.can_flag_own', '1');

        $date = Carbon::parse('2021-01-01T12:00:00+00:00');

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 10, 'title' => 'A', 'created_at' => $date, 'last_posted_at' => $date, 'first_post_id' => 1, 'comment_count' => 2],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 10, 'created_at' => $date, 'type' => 'comment', 'content' => '<t><p>A</p></t>'],
                ['id' => 2, 'number' => 2, 'discussion_id' => 10, 'created_at' => $date, 'type' => 'comment', 'content' => '<t><p>B</p></t>'],
            ],
            Flag::class => [
                ['id' => 20, 'post_id' => 2],
            ],
        ]);
    }

    #[Test]
    public function flagReason()
    {
        $this->sendSuccessfulRequest('POST', '/api/flags', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'reason' => 'off_topic',
                    ],
                    'relationships' => [
                        'post' => [
                            'data' => [
                                'type' => 'posts',
                                'id' => '1',
                            ],
                        ],
                    ],
                ],
            ],
        ], 201);

        $this->assertLogExists('post.flagged', [
            'discussion_id' => 10,
            'post_id' => 1,
            'reason' => 'off_topic',
        ]);
    }

    #[Test]
    public function flagDetail()
    {
        $this->sendSuccessfulRequest('POST', '/api/flags', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'reasonDetail' => 'This and that',
                    ],
                    'relationships' => [
                        'post' => [
                            'data' => [
                                'type' => 'posts',
                                'id' => '1',
                            ],
                        ],
                    ],
                ],
            ],
        ], 201);

        $this->assertLogExists('post.flagged', [
            'discussion_id' => 10,
            'post_id' => 1,
            'reason' => 'other',
        ]);
    }

    #[Test]
    public function delete()
    {
        // flarum-flags' delete endpoint still requires a (possibly empty) JSON body; without
        // it the request 500s. Unlike the core discussion DELETE, this is not covered by the
        // flarum/framework#2896 fix, so the empty-body workaround stays here.
        $this->sendSuccessfulRequest('DELETE', '/api/posts/2/flags', [
            'json' => [],
        ], 204);

        $this->assertLogExists('post.dismissed_flags', [
            'discussion_id' => 10,
            'post_id' => 2,
        ]);
    }

    #[Test]
    public function account_flag_is_audited_without_dereferencing_a_post(): void
    {
        $this->prepareDatabase([User::class => [['id' => 3, 'username' => 'flagtarget', 'email' => 'target@machine.local']]]);
        $this->sendSuccessfulRequest('POST', '/api/flags', ['json' => ['data' => [
            'attributes' => ['reason' => 'spam'],
            'relationships' => ['targetUser' => ['data' => ['type' => 'users', 'id' => '3']]],
        ]]], 201);
        $this->assertLogExists('user.flagged', ['user_id' => 3, 'reason' => 'spam']);
    }

    #[Test]
    public function account_dismissal_is_audited_separately(): void
    {
        $this->prepareDatabase([
            User::class => [['id' => 3, 'username' => 'flagtarget', 'email' => 'target@machine.local']],
            Flag::class => [['id' => 30, 'post_id' => null, 'target_user_id' => 3]],
        ]);
        $this->sendSuccessfulRequest('DELETE', '/api/users/3/flags', [], 204);
        $this->assertLogExists('user.dismissed_flags', ['user_id' => 3]);
    }
}
