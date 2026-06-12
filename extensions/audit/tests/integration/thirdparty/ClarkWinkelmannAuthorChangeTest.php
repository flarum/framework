<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration\thirdparty;

use Carbon\Carbon;
use Flarum\Audit\Tests\integration\TestCase;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class ClarkWinkelmannAuthorChangeTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('clarkwinkelmann-author-change');

        $date = Carbon::parse('2021-01-01T12:00:00+00:00');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'A', 'created_at' => $date, 'last_posted_at' => $date, 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => $date, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>A</p></t>'],
            ],
        ]);
    }

    #[Test]
    public function discussion_create_date()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/discussions/1', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'createdAt' => '2021-02-01T12:00:00+00:00',
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('discussion.create_date_changed', [
            'discussion_id' => 1,
            'old_date' => '2021-01-01T12:00:00+00:00',
            'new_date' => '2021-02-01T12:00:00+00:00',
        ]);
    }

    #[Test]
    public function discussion_user_change()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/discussions/1', [
            'json' => [
                'data' => [
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'users',
                                'id' => '2',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('discussion.user_changed', [
            'discussion_id' => 1,
            'old_user_id' => 1,
            'new_user_id' => 2,
        ]);
    }

    #[Test]
    public function discussion_user_remove()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/discussions/1', [
            'json' => [
                'data' => [
                    'relationships' => [
                        'user' => [
                            'data' => [],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('discussion.user_changed', [
            'discussion_id' => 1,
            'old_user_id' => 1,
            'new_user_id' => null,
        ]);
    }

    #[Test]
    public function post_create_date()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/posts/1', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'createdAt' => '2021-02-01T12:00:00+00:00',
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('post.create_date_changed', [
            'post_id' => 1,
            'discussion_id' => 1,
            'old_date' => '2021-01-01T12:00:00+00:00',
            'new_date' => '2021-02-01T12:00:00+00:00',
        ]);
    }

    #[Test]
    public function post_edit_date()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/posts/1', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'editedAt' => '2021-02-01T12:00:00+00:00',
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('post.edit_date_changed', [
            'post_id' => 1,
            'discussion_id' => 1,
            'old_date' => null,
            'new_date' => '2021-02-01T12:00:00+00:00',
        ]);
    }

    #[Test]
    public function post_user_change()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/posts/1', [
            'json' => [
                'data' => [
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'users',
                                'id' => '2',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('post.user_changed', [
            'post_id' => 1,
            'discussion_id' => 1,
            'old_user_id' => 1,
            'new_user_id' => 2,
        ]);
    }

    #[Test]
    public function post_user_remove()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/posts/1', [
            'json' => [
                'data' => [
                    'relationships' => [
                        'user' => [
                            'data' => [],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('post.user_changed', [
            'post_id' => 1,
            'discussion_id' => 1,
            'old_user_id' => 1,
            'new_user_id' => null,
        ]);
    }
}
