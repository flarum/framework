<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Deleting a discussion deletes its posts in one query, which raises no event
 * for each post. The users those posts mentioned must still be recounted.
 */
class PostDeletionTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mentions');
        $createdAt = Carbon::now()->subHour();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'potato', 'email' => 'potato@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'moderator', 'email' => 'moderator@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 2, 'title' => 'Deleted', 'created_at' => $createdAt, 'last_posted_at' => $createdAt, 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'last_post_number' => 1],
                ['id' => 3, 'title' => 'Kept', 'created_at' => $createdAt, 'last_posted_at' => $createdAt, 'user_id' => 2, 'first_post_id' => 2, 'comment_count' => 1, 'last_post_number' => 1],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 2, 'created_at' => $createdAt, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>first</p></t>'],
                ['id' => 2, 'number' => 1, 'discussion_id' => 3, 'created_at' => $createdAt, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>first</p></t>'],
            ],
        ]);
    }

    #[Test]
    public function deleting_a_discussion_recounts_every_user_its_posts_mentioned()
    {
        $this->reply(2, '@"potato"#3 @"moderator"#4 hello');
        $this->reply(3, '@"potato"#3 hello');

        $this->assertSame(2, $this->mentionCountOf(3));
        $this->assertSame(1, $this->mentionCountOf(4));

        $response = $this->send(
            $this->request('DELETE', '/api/discussions/2', ['authenticatedAs' => 1])
        );

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());

        // Both users the deleted posts mentioned are recounted, and a mention
        // in another discussion still counts.
        $this->assertSame(1, $this->mentionCountOf(3));
        $this->assertSame(0, $this->mentionCountOf(4));
    }

    protected function reply(int $discussionId, string $content, int $userId = 1): int
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => $userId,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => ['content' => $content],
                        'relationships' => [
                            'discussion' => ['data' => ['type' => 'discussions', 'id' => (string) $discussionId]],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());

        return (int) json_decode($response->getBody(), true)['data']['id'];
    }

    protected function mentionCountOf(int $userId): int
    {
        return (int) User::query()->findOrFail($userId)->mention_count;
    }
}
