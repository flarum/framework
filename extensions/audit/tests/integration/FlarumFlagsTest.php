<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

use Carbon\Carbon;

class FlarumFlagsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-flags');

        $this->setting('flarum-flags.can_flag_own', '1');

        $date = Carbon::parse('2021-01-01T12:00:00+00:00');

        $this->prepareDatabase([
            'discussions' => [
                ['id' => 10, 'title' => 'A', 'created_at' => $date, 'last_posted_at' => $date, 'first_post_id' => 1, 'comment_count' => 2],
            ],
            'posts' => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 10, 'created_at' => $date, 'type' => 'comment', 'content' => '<t><p>A</p></t>'],
                ['id' => 2, 'number' => 2, 'discussion_id' => 10, 'created_at' => $date, 'type' => 'comment', 'content' => '<t><p>B</p></t>'],
            ],
            'flags' => [
                ['id' => 20, 'post_id' => 2],
            ],
        ]);
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function delete()
    {
        $this->sendSuccessfulRequest('DELETE', '/api/posts/2/flags', [
            'json' => [], // workaround for https://github.com/flarum/core/issues/2896
        ], 204);

        $this->assertLogExists('post.dismissed_flags', [
            'discussion_id' => 10,
            'post_id' => 2,
        ]);
    }
}
