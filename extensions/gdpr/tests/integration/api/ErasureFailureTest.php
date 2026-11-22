<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\tests\integration\api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Extend\Event;
use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\Post\Event\DeletingPosts;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * An erasure that fails has to be retryable, and retrying it has to be able to
 * put things right: the request is cleared for another run, but the work done
 * by the failed run is already there. A failure after the posts are deleted
 * must therefore take the deletion with it.
 */
class ErasureFailureTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-likes', 'flarum-gdpr');

        $this->setting('mail_driver', 'log');
        $this->setting('flarum-gdpr.allow-deletion', true);

        $this->prepareDatabase([
            User::class => [
                [...$this->normalUser(), 'like_count' => 1],
                ['id' => 3, 'username' => 'erased', 'email' => 'erased@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Their post and a reply', 'user_id' => 3, 'first_post_id' => 1, 'last_post_id' => 2, 'last_post_number' => 2, 'last_posted_user_id' => 2, 'last_posted_at' => Carbon::now(), 'comment_count' => 2, 'participant_count' => 2, 'created_at' => Carbon::now()],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>Opening</p></t>', 'created_at' => Carbon::now()],
                ['id' => 2, 'discussion_id' => 1, 'number' => 2, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Reply</p></t>', 'created_at' => Carbon::now()],
            ],
            'post_likes' => [
                ['post_id' => 1, 'user_id' => 2],
            ],
            'gdpr_erasure' => [
                ['id' => 1, 'user_id' => 3, 'verification_token' => 'abc123', 'status' => ErasureRequest::STATUS_USER_CONFIRMED, 'created_at' => Carbon::now(), 'user_confirmed_at' => Carbon::now()],
            ],
        ]);
    }

    #[Test]
    public function a_failed_erasure_can_be_retried(): void
    {
        $fail = true;
        $this->extend((new Event())->listen(DeletingPosts::class, function () use (&$fail) {
            if ($fail) {
                throw new RuntimeException('Temporary deletion failure');
            }
        }));

        $this->assertEquals(500, $this->attemptErasure()->getStatusCode());

        $erasure = ErasureRequest::query()->findOrFail(1);
        $this->assertSame(ErasureRequest::STATUS_USER_CONFIRMED, $erasure->status);
        $this->assertNull($erasure->processed_at);
        $this->assertSame(1, $erasure->failure_count);
        $this->assertNotNull(User::query()->find(3));
        $this->assertNotNull(Post::query()->find(1));
        $this->assertSame(1, $this->likeCountOf(2));

        $fail = false;
        $this->assertEquals(200, $this->attemptErasure()->getStatusCode());

        $this->assertNull(User::query()->find(3));
        $this->assertNull(Post::query()->find(1));
        $this->assertSame(0, $this->likeCountOf(2));
        $this->assertSame(0, ErasureRequest::query()->findOrFail(1)->failure_count);
    }

    #[Test]
    public function a_failure_after_the_posts_are_deleted_rolls_the_deletion_back(): void
    {
        $fail = true;
        $this->extend((new Event())->listen('eloquent.saving: '.Discussion::class, function () use (&$fail) {
            if ($fail) {
                throw new RuntimeException('Temporary refresh failure');
            }
        }));

        $this->assertEquals(500, $this->attemptErasure()->getStatusCode());

        // Had the deletion been committed on its own, the retry would no
        // longer have the discussion, and the counts would stay wrong.
        $erasure = ErasureRequest::query()->findOrFail(1);
        $this->assertNull($erasure->processed_at);
        $this->assertSame(1, $erasure->failure_count);
        $this->assertNotNull(User::query()->find(3));
        $this->assertNotNull(Post::query()->find(1));
        $this->assertSame(1, $this->likeCountOf(2));

        $fail = false;
        $this->assertEquals(200, $this->attemptErasure()->getStatusCode());

        $discussion = Discussion::query()->findOrFail(1);
        $this->assertNull(User::query()->find(3));
        $this->assertNull(Post::query()->find(1));
        $this->assertNotNull(Post::query()->find(2));
        $this->assertSame(2, $discussion->first_post_id);
        $this->assertSame(2, $discussion->last_post_id);
        $this->assertSame(1, $discussion->comment_count);
        $this->assertSame(1, $discussion->participant_count);
        $this->assertSame(0, $this->likeCountOf(2));
        $this->assertSame(0, ErasureRequest::query()->findOrFail(1)->failure_count);
    }

    protected function attemptErasure(): ResponseInterface
    {
        return $this->send(
            $this->request('PATCH', '/api/user-erasure-requests/1', [
                'authenticatedAs' => 1,
                'json' => ['data' => ['attributes' => ['processedMode' => ErasureRequest::MODE_DELETION]]],
            ])
        );
    }

    protected function likeCountOf(int $userId): int
    {
        return (int) User::query()->findOrFail($userId)->like_count;
    }
}
