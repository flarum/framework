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
use Flarum\Extend;
use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Deletion mode walks the discussions the user posted in. One transaction for
 * the whole walk holds every one of those discussions locked until the end, so
 * a reply to any of them waits for the erasure to finish. Each chunk of
 * discussions is deleted and refreshed in its own transaction instead: a
 * refresh that fails only takes its chunk's deletion with it, and the locks it
 * took are released when its chunk is done.
 */
class ChunkedErasureTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /** Discussions 1-100 are the first chunk, discussion 101 the second. */
    private const DISCUSSIONS = 101;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-gdpr');

        $this->setting('mail_driver', 'log');
        $this->setting('flarum-gdpr.allow-deletion', true);

        $at = fn (int $minute) => Carbon::parse('2026-01-01 00:00:00')->addMinutes($minute);

        $discussions = [];
        $posts = [];

        for ($id = 1; $id <= self::DISCUSSIONS; $id++) {
            $discussions[] = [
                'id' => $id,
                'title' => "Discussion $id",
                'slug' => "discussion-$id",
                'user_id' => 2,
                'first_post_id' => $id * 2 - 1,
                'last_post_id' => $id * 2,
                'last_post_number' => 2,
                'last_posted_user_id' => 2,
                'last_posted_at' => $at($id * 2),
                'comment_count' => 2,
                'participant_count' => 2,
                'created_at' => $at($id * 2 - 1),
            ];
            $posts[] = [
                'id' => $id * 2 - 1, 'discussion_id' => $id, 'number' => 1, 'user_id' => 5,
                'type' => 'comment', 'content' => '<t><p>Their post</p></t>', 'created_at' => $at($id * 2 - 1),
            ];
            $posts[] = [
                'id' => $id * 2, 'discussion_id' => $id, 'number' => 2, 'user_id' => 2,
                'type' => 'comment', 'content' => '<t><p>Someone else</p></t>', 'created_at' => $at($id * 2),
            ];
        }

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 5, 'username' => 'erased', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'erased@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => $discussions,
            Post::class => $posts,
            'gdpr_erasure' => [
                ['id' => 1, 'user_id' => 5, 'verification_token' => 'abc123', 'status' => ErasureRequest::STATUS_USER_CONFIRMED, 'created_at' => $at(1), 'user_confirmed_at' => $at(1)],
            ],
        ]);
    }

    protected function allowedRepeatedQueries(): array
    {
        // The erasure refreshes every discussion it deleted a post from: its
        // first and last post, its comment and participant counts, and the
        // discussion row. That is one refresh per discussion by design, and
        // this fixture is large enough to need two chunks of them.
        return [
            'select exists(select * from ',
            'asc limit ?',
            'select count(*) as ',
            'select count(distinct ',
            'comment_count',
        ];
    }

    #[Test]
    public function a_failed_refresh_leaves_only_its_own_chunk_for_the_retry(): void
    {
        $this->extend((new Extend\Event())->listen('eloquent.saving: '.Discussion::class, function (Discussion $discussion) {
            // The last discussion is in the second chunk, so the first chunk
            // has already been committed when this runs.
            if ($discussion->id === self::DISCUSSIONS) {
                throw new RuntimeException('Temporary refresh failure');
            }
        }));

        $this->assertSame(500, $this->attemptErasure()->getStatusCode());

        // The erasure failed, so the user and the request are still there...
        $this->assertNotNull(User::query()->find(5));
        $this->assertSame(1, (int) ErasureRequest::query()->findOrFail(1)->failure_count);

        // ...and the discussion that could not be refreshed still has the
        // post the failed chunk deleted, for the retry to finish.
        $this->assertNotNull(Post::query()->find(self::DISCUSSIONS * 2 - 1));

        // The first chunk was committed on its own: nothing of it is back.
        $this->assertSame(
            [self::DISCUSSIONS * 2 - 1],
            array_map('intval', Post::query()->where('user_id', 5)->orderBy('id')->pluck('id')->all()),
            'Only the failed chunk should still have the author\'s posts.'
        );
        $this->assertSame(0, Post::query()->where('user_id', 5)->where('discussion_id', 1)->count());

        // Its discussion was refreshed before it was released.
        $discussion = Discussion::query()->findOrFail(1);
        $this->assertSame(2, $discussion->first_post_id);
        $this->assertSame(2, $discussion->last_post_id);
        $this->assertSame(1, $discussion->comment_count);
        $this->assertSame(1, $discussion->participant_count);
    }

    #[Test]
    public function the_retry_after_a_failed_chunk_finishes_the_erasure(): void
    {
        $fail = true;

        $this->extend((new Extend\Event())->listen('eloquent.saving: '.Discussion::class, function (Discussion $discussion) use (&$fail) {
            if ($fail && $discussion->id === self::DISCUSSIONS) {
                throw new RuntimeException('Temporary refresh failure');
            }
        }));

        $this->assertSame(500, $this->attemptErasure()->getStatusCode());

        $fail = false;

        $this->assertSame(200, $this->attemptErasure()->getStatusCode());

        $this->assertNull(User::query()->find(5));
        $this->assertSame(0, Post::query()->where('user_id', 5)->count());
        $this->assertSame(0, ErasureRequest::query()->findOrFail(1)->failure_count);

        // Every discussion was refreshed from what its chunk left behind.
        foreach ([1, 50, 100, self::DISCUSSIONS] as $id) {
            $discussion = Discussion::query()->findOrFail($id);
            $this->assertSame($id * 2, $discussion->first_post_id);
            $this->assertSame(1, $discussion->comment_count);
            $this->assertSame(1, $discussion->participant_count);
        }
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
}
