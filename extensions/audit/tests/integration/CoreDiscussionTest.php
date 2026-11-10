<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

class CoreDiscussionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $date = Carbon::parse('2021-01-01T12:00:00+00:00');

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => 'A', 'created_at' => $date, 'last_posted_at' => $date, 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'B', 'created_at' => $date, 'last_posted_at' => $date, 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'hidden_at' => $date],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => $date, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>A</p></t>'],
            ],
        ]);
    }

    #[Test]
    public function deleted()
    {
        $this->sendSuccessfulRequest('DELETE', '/api/discussions/1', [], 204);

        $this->assertLogExists('discussion.deleted', [
            'discussion_id' => 1,
        ]);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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
