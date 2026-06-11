<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

use Carbon\Carbon;
use Illuminate\Support\Arr;

class CorePostTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $date = Carbon::parse('2021-01-01T12:00:00+00:00');

        $this->prepareDatabase([
            'discussions' => [
                ['id' => 10, 'title' => 'A', 'created_at' => $date, 'last_posted_at' => $date, 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 2, 'post_number_index' => 2, 'last_post_number' => 2],
            ],
            'posts' => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 10, 'created_at' => $date, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>A</p></t>'],
                ['id' => 2, 'number' => 2, 'discussion_id' => 10, 'created_at' => $date, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>B</p></t>', 'hidden_at' => $date],
            ],
        ]);
    }

    /**
     * @test
     */
    public function deleted()
    {
        $this->sendSuccessfulRequest('DELETE', '/api/posts/1', [], 204);

        $this->assertLogExists('post.deleted', [
            'discussion_id' => 10,
            'post_id' => 1,
        ]);
    }

    /**
     * @test
     */
    public function hidden()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/posts/1', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'isHidden' => true,
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('post.hidden', [
            'discussion_id' => 10,
            'post_id' => 1,
        ]);
    }

    /**
     * @test
     */
    public function posted()
    {
        $response = $this->sendSuccessfulRequest('POST', '/api/posts', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'content' => 'CCC',
                    ],
                    'relationships' => [
                        'discussion' => [
                            'data' => [
                                'type' => 'discussions',
                                'id' => 10,
                            ],
                        ],
                    ],
                ],
            ],
        ], 201);

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertLogExists('post.created', [
            'discussion_id' => 10,
            'post_id' => Arr::get($body, 'data.id'),
        ]);
    }

    /**
     * @test
     */
    public function restored()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/posts/2', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'isHidden' => false,
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('post.restored', [
            'discussion_id' => 10,
            'post_id' => 2,
        ]);
    }

    /**
     * @test
     */
    public function revised()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/posts/1', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'content' => 'AAA',
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('post.revised', [
            'discussion_id' => 10,
            'post_id' => 1,
        ]);
    }
}
