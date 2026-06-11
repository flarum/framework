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

class CoreDiscussionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $date = Carbon::parse('2021-01-01T12:00:00+00:00');

        $this->prepareDatabase([
            'discussions' => [
                ['id' => 1, 'title' => 'A', 'created_at' => $date, 'last_posted_at' => $date, 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'post_number_index' => 1],
                ['id' => 2, 'title' => 'B', 'created_at' => $date, 'last_posted_at' => $date, 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'post_number_index' => 1, 'hidden_at' => $date],
            ],
            'posts' => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => $date, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>A</p></t>'],
            ],
        ]);
    }

    /**
     * @test
     */
    public function deleted()
    {
        $this->sendSuccessfulRequest('DELETE', '/api/discussions/1', [
            'json' => [], // workaround for https://github.com/flarum/core/issues/2896
        ], 204);

        $this->assertLogExists('discussion.deleted', [
            'discussion_id' => 1,
        ]);
    }

    /**
     * @test
     */
    public function hidden()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/discussions/1', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'isHidden' => true,
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('discussion.hidden', [
            'discussion_id' => 1,
        ]);
    }

    /**
     * @test
     */
    public function renamed()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/discussions/1', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'title' => 'AAA',
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('discussion.renamed', [
            'discussion_id' => 1,
            'old_title' => 'A',
            'new_title' => 'AAA',
        ]);
    }

    /**
     * @test
     */
    public function restored()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/discussions/2', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'isHidden' => false,
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('discussion.restored', [
            'discussion_id' => 2,
        ]);
    }

    /**
     * @test
     */
    public function started()
    {
        $response = $this->sendSuccessfulRequest('POST', '/api/discussions', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'title' => 'CCC',
                        'content' => 'CCC',
                    ],
                ],
            ],
        ], 201);

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertLogExists('discussion.created', [
            'discussion_id' => Arr::get($body, 'data.id'),
        ]);

        $this->assertLogDoesntExist('post.created');
    }
}
