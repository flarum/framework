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
use Flarum\Discussion\Event\Deleted;
use Flarum\Extend;
use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\Post\Event\DeletingPosts;
use Flarum\Post\Post;
use Flarum\Testing\integration\Extend\BindInlineTransactionsManager;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * How an erasure decides it is finished.
 *
 * The discussion scan and the deletion are not atomic: a pass can find nothing
 * to delete in the chunk it scanned while the author has a post somewhere the
 * scan missed, and a chunk's domain events must not escape before the chunk
 * commits.
 */
class ErasurePassesTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-gdpr');

        $this->setting('mail_driver', 'log');
        $this->setting('flarum-gdpr.allow-deletion', true);

        $at = fn (int $minute) => Carbon::parse('2026-01-01 00:00:00')->addMinutes($minute);

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 5, 'username' => 'erased', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'erased@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'First', 'slug' => 'first', 'user_id' => 2, 'first_post_id' => 1, 'last_post_id' => 2, 'last_post_number' => 2, 'last_posted_user_id' => 2, 'last_posted_at' => $at(2), 'comment_count' => 2, 'participant_count' => 2, 'created_at' => $at(1)],
                ['id' => 2, 'title' => 'Second', 'slug' => 'second', 'user_id' => 2, 'first_post_id' => 3, 'last_post_id' => 4, 'last_post_number' => 2, 'last_posted_user_id' => 2, 'last_posted_at' => $at(4), 'comment_count' => 2, 'participant_count' => 2, 'created_at' => $at(3)],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 5, 'type' => 'comment', 'content' => '<t><p>Their post</p></t>', 'created_at' => $at(1)],
                ['id' => 2, 'discussion_id' => 1, 'number' => 2, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Reply</p></t>', 'created_at' => $at(2)],
                ['id' => 3, 'discussion_id' => 2, 'number' => 1, 'user_id' => 5, 'type' => 'comment', 'content' => '<t><p>Their post</p></t>', 'created_at' => $at(3)],
                ['id' => 4, 'discussion_id' => 2, 'number' => 2, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Reply</p></t>', 'created_at' => $at(4)],
            ],
            'gdpr_erasure' => [
                ['id' => 1, 'user_id' => 5, 'verification_token' => 'abc123', 'status' => ErasureRequest::STATUS_USER_CONFIRMED, 'created_at' => $at(1), 'user_confirmed_at' => $at(1)],
            ],
        ]);
    }

    protected function allowedRepeatedQueries(): array
    {
        // The limit test runs every one of the ten passes, which is the point
        // of it: the same deletion and refresh shapes run again for the
        // discussion the author replied in each time.
        $grammar = $this->database()->getQueryGrammar();
        $posts = $grammar->wrapTable('posts');
        $discussions = $grammar->wrapTable('discussions');
        $users = $grammar->wrapTable('users');
        $notifications = $grammar->wrapTable('notifications');
        $id = $grammar->wrap('id');
        $discussionId = $grammar->wrap('posts.discussion_id');
        $aggregate = $grammar->wrap('aggregate');

        return [
            "delete from $posts where ".$grammar->wrap('user_id').' = ?',
            "delete from $notifications where",
            "insert into $posts ",
            "select * from $discussions where $id in",
            "select $id from $discussions where",
            "select $id from $posts where $id in",
            // The scan, and the chunk's own lookup of the discussions it deleted.
            'select distinct '.$grammar->wrap('discussion_id')." from $posts where",
            'select distinct '.$grammar->wrap('posts.discussion_id')." from $posts where",
            "select $id from $users where $id in",
            "select exists(select * from $posts where",
            "select count(*) as $aggregate from $posts where $discussionId = ?",
            "from $users inner join $posts on",
        ];
    }

    /**
     * The pass was about to delete the author's posts when they went away, and
     * a reply they wrote in another discussion is there instead. A pass that
     * deletes nothing is a rescan, not the end of the erasure.
     */
    #[Test]
    public function a_pass_that_deletes_nothing_rescans_for_a_reply_written_meanwhile(): void
    {
        $replied = false;
        $at = Carbon::parse('2026-01-01 00:10:00');

        $this->extend((new Extend\Event())->listen(DeletingPosts::class, function () use (&$replied, $at) {
            if ($replied) {
                return;
            }

            $replied = true;
            $db = $this->database();

            // The post the chunk was scanned for is gone, so its deletion
            // matches nothing...
            $db->table('posts')->where('user_id', 5)->delete();

            // ...and the author has written a reply in another discussion.
            $db->table('discussions')->insert([
                'id' => 3, 'title' => 'Elsewhere', 'slug' => 'elsewhere', 'user_id' => 2,
                'first_post_id' => null, 'last_post_id' => null, 'last_post_number' => 1,
                'last_posted_user_id' => 2, 'last_posted_at' => $at, 'comment_count' => 2,
                'participant_count' => 2, 'created_at' => $at,
            ]);
            $db->table('posts')->insert([
                ['id' => 5, 'discussion_id' => 3, 'number' => 1, 'user_id' => 2, 'type' => 'comment',
                    'content' => '<t><p>Opening</p></t>', 'created_at' => $at],
                ['id' => 6, 'discussion_id' => 3, 'number' => 2, 'user_id' => 5, 'type' => 'comment',
                    'content' => '<t><p>Reply after the scan</p></t>', 'created_at' => $at],
            ]);
            $db->table('discussions')->where('id', 3)->update(['first_post_id' => 5, 'last_post_id' => 6]);
        }));

        $this->assertSame(200, $this->attemptErasure()->getStatusCode());

        $this->assertTrue($replied, 'The reply should have been written while the erasure was running.');
        $this->assertNull(Post::find(6), 'A reply written while the erasure ran must not be left behind.');
        $this->assertNotNull(Post::find(5), 'The reply is not theirs.');
        $this->assertNull(User::find(5));
    }

    /**
     * A user who keeps posting while the erasure runs can outlast its passes.
     * The erasure then fails, so it is retried, rather than answering success
     * with their posts still there.
     */
    #[Test]
    public function running_out_of_passes_with_posts_left_fails_the_erasure(): void
    {
        // They keep posting: the post is written again as soon as the pass
        // has removed it, so there is one left after every pass.
        $this->extend((new Extend\Event())->listen(DeletingPosts::class, function (DeletingPosts $event) {
            $at = Carbon::parse('2026-01-01 00:20:00');

            $event->afterDeletion(function () use ($at) {
                $this->database()->table('posts')->insert([
                    'id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 5,
                    'type' => 'comment', 'content' => '<t><p>Still here</p></t>', 'created_at' => $at,
                ]);
            });
        }));

        $this->assertSame(500, $this->attemptErasure()->getStatusCode());

        // The request is open for a retry, and the posts are still there.
        $erasure = ErasureRequest::query()->findOrFail(1);
        $this->assertSame(ErasureRequest::STATUS_USER_CONFIRMED, $erasure->status);
        $this->assertSame(1, (int) $erasure->failure_count);
        $this->assertNotNull(User::find(5));
        $this->assertTrue(Post::query()->where('user_id', 5)->exists());
    }

    /**
     * A discussion the chunk empties is deleted, and its `Deleted` event tells
     * the rest of the forum about it (a tag count, for instance). If a later
     * refresh in the same chunk fails, the chunk rolls back and its posts come
     * back, so the event must not have been dispatched: the retry would
     * dispatch it a second time.
     */
    #[Test]
    public function a_chunk_that_fails_does_not_dispatch_its_deleted_event(): void
    {
        $dispatched = 0;
        $fail = true;

        $this->extend(
            new BindInlineTransactionsManager(),
            (new Extend\Event())->listen(Deleted::class, function () use (&$dispatched) {
                $dispatched++;
            }),
            (new Extend\Event())->listen('eloquent.saving: '.Discussion::class, function (Discussion $discussion) use (&$fail) {
                // Discussion 1 is refreshed (and deleted) first; the refresh of
                // discussion 2 fails after it.
                if ($fail && $discussion->id === 2) {
                    throw new RuntimeException('Temporary refresh failure');
                }
            })
        );

        // Discussion 1 holds only the author's post, so emptying it deletes
        // the discussion.
        $this->database()->table('posts')->where('id', 2)->delete();

        $this->assertSame(500, $this->attemptErasure()->getStatusCode());

        $this->assertSame(0, $dispatched, 'A chunk that rolled back must not dispatch the events of the work it undid.');

        $fail = false;

        $this->assertSame(200, $this->attemptErasure()->getStatusCode());

        $this->assertSame(1, $dispatched, 'The retried chunk dispatches the event once.');
        $this->assertNull(Discussion::find(1));
        $this->assertNull(User::find(5));
    }

    #[Test]
    public function a_rolled_back_outer_transaction_does_not_dispatch_the_chunks_events(): void
    {
        $dispatched = 0;
        $this->extend(
            new BindInlineTransactionsManager(),
            (new Extend\Event())->listen(Deleted::class, function () use (&$dispatched) {
                $dispatched++;
            })
        );

        $db = $this->database();
        $db->table('posts')->where('id', 2)->delete();
        $db->beginTransaction();

        try {
            $this->assertSame(200, $this->attemptErasure()->getStatusCode());
            $this->assertSame(0, $dispatched);
        } finally {
            $db->rollBack();
        }

        $this->assertNotNull(Discussion::find(1));
        $this->assertSame(0, $dispatched);
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
