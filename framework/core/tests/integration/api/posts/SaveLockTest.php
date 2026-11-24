<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\posts;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Events\QueryExecuted;
use PHPUnit\Framework\Attributes\Test;

/**
 * A save only locks its discussion when it changes visibility. Ordinary
 * edits, and requests that do not change anything, must not wait on a held
 * discussion lock.
 */
class SaveLockTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => 'Locked discussion', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 2, 'is_private' => 0],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Own post</p></t>', 'is_private' => 0],
                ['id' => 2, 'number' => 2, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Admin post</p></t>', 'is_private' => 0],
            ],
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    #[Test]
    public function an_ordinary_edit_does_not_take_the_discussion_lock(): void
    {
        $db = $this->database();
        $level = $db->transactionLevel();
        $updateLevel = null;
        $locked = false;
        $this->watchWrites($db, $updateLevel, $locked);

        $post = CommentPost::find(1);
        $post->content = '<t><p>Edited</p></t>';
        $post->save();

        $this->assertSame('<t><p>Edited</p></t>', $post->refresh()->content);
        $this->assertSame($level, $updateLevel, 'An ordinary edit should not run in its own transaction.');
        $this->assertFalse($locked, 'An ordinary edit should not lock the discussion.');
    }

    #[Test]
    public function an_edit_that_changes_visibility_takes_the_discussion_lock(): void
    {
        $db = $this->database();
        $level = $db->transactionLevel();
        $updateLevel = null;
        $locked = false;
        $this->watchWrites($db, $updateLevel, $locked);

        $post = CommentPost::find(1);
        $post->is_private = true;
        $post->save();

        $this->assertTrue($post->refresh()->is_private);
        $this->assertSame($level + 1, $updateLevel, 'A visibility update should run in its own transaction.');

        if ($db->getDriverName() !== 'sqlite') {
            $this->assertTrue($locked, 'A visibility update should lock the discussion.');
        }
    }

    #[Test]
    public function a_visibility_change_from_a_saving_listener_is_locked_too(): void
    {
        $db = $this->database();
        $level = $db->transactionLevel();
        $updateLevel = null;
        $locked = false;
        $this->watchWrites($db, $updateLevel, $locked);

        // The listener changes visibility after save() has made its decision,
        // the way core's ModelPrivate checkers do during `saving`.
        CommentPost::saving(function (CommentPost $post) {
            $post->is_private = true;
        });

        $post = CommentPost::find(1);
        $post->save();

        $this->assertTrue($post->refresh()->is_private);
        $this->assertSame($level + 1, $updateLevel, 'A visibility update should run in its own transaction.');

        if ($db->getDriverName() !== 'sqlite') {
            $this->assertTrue($locked, 'A visibility update should lock the discussion.');
        }
    }

    /**
     * Two requests load a post before either saves, as two moderators
     * approving the same post do. The change has to be decided against the row
     * the lock protects, not the snapshot loaded before the wait, or the
     * second request repeats the first one's work (a like count moved twice).
     */
    #[Test]
    public function a_visibility_save_decides_from_the_row_under_the_lock(): void
    {
        $this->database();

        $first = CommentPost::findOrFail(2);
        $second = CommentPost::findOrFail(2);

        $first->is_private = true;
        $first->save();

        $originalSeenByListener = null;
        CommentPost::updated(function (CommentPost $post) use (&$originalSeenByListener) {
            $originalSeenByListener = $post->getOriginal('is_private');
        });

        // Something else changes too, so the update and its listeners run.
        $second->is_private = true;
        $second->content = '<t><p>Edited</p></t>';
        $second->save();

        $this->assertTrue($originalSeenByListener, 'An updated listener should see the row the lock protected as the original.');
        $this->assertArrayNotHasKey('is_private', $second->getChanges(), 'The locked row already had that visibility.');
        $this->assertSame('<t><p>Edited</p></t>', $second->refresh()->content);
    }

    #[Test]
    public function an_empty_patch_on_another_users_post_does_not_take_the_discussion_lock(): void
    {
        $db = $this->database();
        $level = $db->transactionLevel();
        $maxLevel = $level;
        $locked = false;
        $savingRan = false;

        $db->listen(function (QueryExecuted $query) use ($db, &$maxLevel, &$locked) {
            $maxLevel = max($maxLevel, $db->transactionLevel());

            if (str_ends_with($query->sql, 'for update')) {
                $locked = true;
            }
        });
        CommentPost::saving(function () use (&$savingRan) {
            $savingRan = true;
        });

        $response = $this->send($this->request('PATCH', '/api/posts/2', [
            'authenticatedAs' => 2,
            'json' => ['data' => ['attributes' => []]],
        ]));

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertTrue($savingRan, 'The empty PATCH should still reach the model save.');
        $this->assertSame($level, $maxLevel, 'The request should not open a transaction.');
        $this->assertFalse($locked, 'The request should not lock the discussion.');
    }

    #[Test]
    public function an_empty_patch_does_not_wait_for_a_held_discussion_lock(): void
    {
        $db = $this->database();

        if ($db->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('The contention probe uses PostgreSQL row locks.');
        }

        $observer = (new ConnectionFactory(new Container()))->make($db->getConfig());
        $id = random_int(1000000000, 2000000000);
        $now = Carbon::now();

        try {
            // Commit the fixture on the observer connection so both sessions
            // can see it while the test transaction stays open.
            $observer->transaction(function () use ($observer, $id, $now) {
                $observer->table('users')->insert([
                    'id' => $id, 'username' => "savelock$id", 'email' => "savelock$id@example.test",
                    'password' => '', 'joined_at' => $now, 'is_email_confirmed' => true,
                ]);
                $observer->table('discussions')->insert([
                    'id' => $id, 'title' => 'Save lock contention', 'slug' => "save-lock-$id", 'user_id' => $id, 'created_at' => $now,
                ]);
                $observer->table('posts')->insert([
                    'id' => $id, 'number' => 1, 'discussion_id' => $id, 'user_id' => $id, 'created_at' => $now,
                    'type' => 'comment', 'content' => '<t><p>Someone else\'s post</p></t>',
                ]);
                $observer->table('discussions')->where('id', $id)->update([
                    'first_post_id' => $id, 'last_post_id' => $id, 'last_post_number' => 1, 'comment_count' => 1,
                ]);
            });

            // Hold the discussion's lock while the request is handled, and
            // make the request fail instead of waiting if it tries to lock it.
            $observer->beginTransaction();
            $observer->table('discussions')->where('id', $id)->lockForUpdate()->first(['id']);
            $db->statement("SET lock_timeout = '1s'");

            $response = $this->send($this->request('PATCH', "/api/posts/$id", [
                'authenticatedAs' => 2,
                'json' => ['data' => ['attributes' => []]],
            ]));

            $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        } finally {
            $db->statement('SET lock_timeout = 0');
            $observer->rollBack();

            try {
                $observer->table('posts')->where('id', $id)->delete();
                $observer->table('discussions')->where('id', $id)->delete();
                $observer->table('users')->where('id', $id)->delete();
            } finally {
                $observer->disconnect();
            }
        }
    }

    /**
     * Record the transaction level of the post update and whether a statement
     * waited on the discussion row. SQLite does not emit FOR UPDATE, so there
     * the transaction level is what tells whether the lock was taken: the lock
     * only ever runs inside its own transaction.
     */
    private function watchWrites(Connection $db, ?int &$updateLevel, bool &$locked): void
    {
        $posts = $db->getQueryGrammar()->wrapTable('posts');
        $discussions = $db->getQueryGrammar()->wrapTable('discussions');

        $db->listen(function (QueryExecuted $query) use ($db, $posts, $discussions, &$updateLevel, &$locked) {
            if (str_ends_with($query->sql, 'for update') && str_contains($query->sql, "from $discussions ")) {
                $locked = true;
            }

            if (str_starts_with($query->sql, "update $posts ")) {
                $updateLevel = $db->transactionLevel();
            }
        });
    }
}
