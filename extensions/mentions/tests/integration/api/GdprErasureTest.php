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
use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Erasing a user in deletion mode deletes their posts in one query, which
 * raises no event for each post. The users those posts mentioned must still be
 * recounted.
 */
class GdprErasureTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mentions', 'flarum-gdpr');

        $this->setting('mail_driver', 'log');
        $this->setting('flarum-gdpr.allow-deletion', true);

        $createdAt = Carbon::now()->subHour();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'potato', 'email' => 'potato@machine.local', 'is_email_confirmed' => 1],
                ['id' => 5, 'username' => 'erased', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'erased@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 2, 'title' => 'Replied to by them', 'created_at' => $createdAt, 'last_posted_at' => $createdAt, 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'last_post_number' => 1],
                ['id' => 3, 'title' => 'Someone else', 'created_at' => $createdAt, 'last_posted_at' => $createdAt, 'user_id' => 2, 'first_post_id' => 2, 'comment_count' => 1, 'last_post_number' => 1],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 2, 'created_at' => $createdAt, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>first</p></t>'],
                ['id' => 2, 'number' => 1, 'discussion_id' => 3, 'created_at' => $createdAt, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>first</p></t>'],
            ],
            'gdpr_erasure' => [
                ['id' => 1, 'user_id' => 5, 'verification_token' => 'abc123', 'status' => 'user_confirmed', 'reason' => 'Forget me', 'created_at' => $createdAt, 'user_confirmed_at' => $createdAt],
            ],
        ]);
    }

    #[Test]
    public function erasing_a_user_recounts_every_user_their_posts_mentioned()
    {
        // The erased user's replies mention potato twice. Someone else's post
        // mentions potato too, and must keep counting.
        $this->reply(2, '@"potato"#3 hello', 5);
        $this->agePostsBy(5);
        $this->reply(3, '@"potato"#3 again', 5);
        $this->reply(3, '@"potato"#3 from someone else', 2);

        $this->assertSame(3, $this->mentionCountOf(3));

        $response = $this->send(
            $this->request('PATCH', '/api/user-erasure-requests/1', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'processorComment' => 'Done',
                            'processedMode' => ErasureRequest::MODE_DELETION,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNull(User::find(5));

        $this->assertSame(1, $this->mentionCountOf(3));
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

    /**
     * Backdate a user's posts so their next reply is not held by the flood
     * throttle, which only lets one recent post through per user.
     */
    protected function agePostsBy(int $userId): void
    {
        $old = Carbon::now()->subMinute();

        $this->database()->table('posts')->where('user_id', $userId)->update([
            'created_at' => $old,
            'edited_at' => $old,
        ]);
    }
}
