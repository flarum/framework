<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Extend\Event;
use Flarum\Post\CommentPost;
use Flarum\Post\Event\DeletingPosts;
use Flarum\Testing\integration\TestCase;
use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Query\Expression;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * A moderator deletes a post while one of its likers likes a post in another
 * discussion.
 *
 * The liker's counter update commits after the deletion has read the counts
 * and before the deletion locks the users it is about to change. On MariaDB
 * 11.6+ (with `innodb_snapshot_isolation` on by default) that is a conflict
 * the database reports as 1213 or 1020 instead of waiting, so a single attempt
 * fails. The deletion has to retry and still remove the post, decrement its
 * liker once and keep the like that was made meanwhile.
 *
 * These tests run the deletion outside the test transaction, because a retry
 * only works on the outermost transaction: InnoDB rolls the whole transaction
 * back on a deadlock, so a nested retry would repeat a savepoint that is gone.
 */
class PostDeleteConflictTest extends TestCase
{
    #[Test]
    public function a_delete_survives_a_liker_committing_meanwhile(): void
    {
        $this->extension('flarum-likes');

        $db = $this->database();
        if ($db->getDriverName() === 'sqlite') {
            // SQLite's rollback journal cannot commit a second connection
            // while the first has a read transaction open.
            $this->markTestSkipped('The replay needs a second connection to commit while the deletion reads.');
        }

        $observer = (new ConnectionFactory(new Container()))->make($db->getConfig());
        $id = random_int(1000000000, 2000000000);
        $now = Carbon::now()->toDateTimeString();

        // The liker liked the post that is deleted. The post they like while
        // the deletion runs is in a discussion of its own: in the same one the
        // like would wait for the deletion's lock.
        $liker = $id;
        $discussion = $id;
        $deletedPost = $id - 1;
        $likedMeanwhile = $id - 2;

        try {
            // Commit the fixture on the observer so both sessions can see it
            // while the deletion runs outside the test transaction.
            $observer->transaction(fn () => $this->insertFixture($observer, $liker, $discussion, $deletedPost, $likedMeanwhile, $now));

            $db->rollBack();

            $replayed = false;
            $db->listen(function (QueryExecuted $query) use ($observer, $liker, $likedMeanwhile, &$replayed) {
                if ($replayed || stripos($query->sql, 'count(') === false || ! str_contains($query->sql, 'post_likes')) {
                    return;
                }

                $replayed = true;

                // The liker likes the post in the other discussion and their
                // counter update commits: the committed half of a like whose
                // own locks are already released.
                $observer->transaction(function () use ($observer, $liker, $likedMeanwhile) {
                    $observer->table('post_likes')->insert(['post_id' => $likedMeanwhile, 'user_id' => $liker]);
                    $observer->table('users')->where('id', $liker)->update(['like_count' => new Expression('like_count + 1')]);
                });
            });

            $response = $this->send($this->request('DELETE', "/api/posts/$deletedPost", ['authenticatedAs' => 1]));

            $this->assertSame(204, $response->getStatusCode(), (string) $response->getBody());
            $this->assertTrue($replayed, 'The liker should commit between the count read and the user lock.');
            // Pins the rows the retried delete removed, kept and wrote: these
            // are read straight from the tables, not from the response.
            $this->assertFalse($observer->table('posts')->where('id', $deletedPost)->exists());
            $this->assertFalse($observer->table('post_likes')->where('post_id', $deletedPost)->exists());
            $this->assertTrue($observer->table('post_likes')->where('post_id', $likedMeanwhile)->where('user_id', $liker)->exists());
            // One like before, one made meanwhile, one post deleted.
            $this->assertSame(1, (int) $observer->table('users')->where('id', $liker)->value('like_count'));
        } finally {
            $this->closeTransaction($db);

            $observer->transaction(fn () => $this->removeFixture($observer, $liker, $discussion, $likedMeanwhile));
            $observer->disconnect();
        }
    }

    #[Test]
    public function a_delete_retries_a_conflict_without_decrementing_twice(): void
    {
        $this->extension('flarum-likes');

        $attempts = 0;
        // The conflict only surfaces after the post row is gone, the way a
        // rejected user lock or counter update does. The retry has to delete
        // the row again rather than trust the rolled back attempt.
        $this->extend((new Event())->listen(DeletingPosts::class, function (DeletingPosts $event) use (&$attempts) {
            if (++$attempts === 1) {
                $event->afterDeletion(function () {
                    throw new PDOException('Deadlock found when trying to get lock; try restarting transaction', 1213);
                });
            }
        }));

        $db = $this->database();
        $id = random_int(1000000000, 2000000000);
        $now = Carbon::now()->toDateTimeString();
        $liker = $id;
        $discussion = $id;
        $deletedPost = $id - 1;

        $db->rollBack();

        try {
            $this->insertFixture($db, $liker, $discussion, $deletedPost, null, $now);

            $response = $this->send($this->request('DELETE', "/api/posts/$deletedPost", ['authenticatedAs' => 1]));

            $this->assertSame(204, $response->getStatusCode(), (string) $response->getBody());
            $this->assertSame(2, $attempts, 'The deletion should have been attempted twice.');
            $this->assertFalse($db->table('posts')->where('id', $deletedPost)->exists());
            $this->assertFalse($db->table('post_likes')->where('post_id', $deletedPost)->exists());
            $this->assertSame(0, (int) $db->table('users')->where('id', $liker)->value('like_count'));
        } finally {
            $this->closeTransaction($db);
            $this->removeFixture($db, $liker, $discussion, null);
        }
    }

    #[Test]
    public function a_discussion_delete_retries_a_conflict_without_decrementing_twice(): void
    {
        $this->extension('flarum-likes');

        $attempts = 0;
        $this->extend((new Event())->listen(DeletingPosts::class, function (DeletingPosts $event) use (&$attempts) {
            if (++$attempts === 1) {
                $event->afterDeletion(function () {
                    throw new PDOException('Deadlock found when trying to get lock; try restarting transaction', 1213);
                });
            }
        }));

        $db = $this->database();
        $id = random_int(1000000000, 2000000000);
        $now = Carbon::now()->toDateTimeString();
        $liker = $id;
        $discussion = $id;
        $deletedPost = $id - 1;

        $db->rollBack();

        try {
            $this->insertFixture($db, $liker, $discussion, $deletedPost, null, $now);

            // Deleting a discussion retries at its own transaction, so the
            // cascade as a whole has to be repeatable.
            $response = $this->send($this->request('DELETE', "/api/discussions/$discussion", ['authenticatedAs' => 1]));

            $this->assertSame(204, $response->getStatusCode(), (string) $response->getBody());
            $this->assertSame(2, $attempts, 'The deletion should have been attempted twice.');
            $this->assertFalse($db->table('discussions')->where('id', $discussion)->exists());
            $this->assertFalse($db->table('posts')->where('discussion_id', $discussion)->exists());
            $this->assertSame(0, (int) $db->table('users')->where('id', $liker)->value('like_count'));
        } finally {
            $this->closeTransaction($db);
            $this->removeFixture($db, $liker, $discussion, null);
        }
    }

    /**
     * A like that commits before the deletion reads the likes must be counted
     * and removed from the liker's counter when the post is deleted.
     *
     * MySQL and MariaDB create the transaction's read view at its first
     * consistent read. The deletion used to look the discussions up from posts
     * inside its locking statement, which reads posts before it waits for the
     * discussion lock, so its view could predate a like that committed while it
     * waited; the liker aggregate then missed the like, the cascade removed the
     * pivot row, and the counter stayed at 1.
     *
     * The review reproduced that with a second session holding the discussion
     * lock while the deletion waited. The suite runs one PHP process per test,
     * so it cannot block a session and commit from another at the same time:
     * the like is committed from the deletion connection's own query listener,
     * at the point between the discussion lock and the liker aggregate where
     * the waiting session's commit lands.
     */
    #[Test]
    #[DataProvider('deletionTransactions')]
    public function a_like_committed_before_the_likes_are_read_is_removed_with_the_post(bool $withSnapshot): void
    {
        $this->extension('flarum-likes');

        $db = $this->database();
        if (! in_array($db->getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Only MySQL and MariaDB give the transaction a read view, which is what makes the order of its reads matter.');
        }

        $observer = (new ConnectionFactory(new Container()))->make($db->getConfig());
        $id = random_int(1000000000, 2000000000);
        $now = Carbon::now()->toDateTimeString();
        $liker = $id;
        $discussion = $id;
        $deletedPost = $id - 1;

        try {
            $observer->transaction(fn () => $this->insertFixture($observer, $liker, $discussion, $deletedPost, null, $now));

            // The liker has not liked the post yet: their like is in flight.
            $observer->table('post_likes')->where('post_id', $deletedPost)->delete();
            $observer->table('users')->where('id', $liker)->update(['like_count' => 0]);

            $db->rollBack();
            if ($withSnapshot) {
                $db->statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
                $db->beginTransaction();
                $this->assertFalse($db->table('post_likes')->where('post_id', $deletedPost)->exists());
            }

            $observer->beginTransaction();
            $observer->table('users')->where('id', $liker)->lockForUpdate()->get(['id']);
            $observer->table('post_likes')->insert(['post_id' => $deletedPost, 'user_id' => $liker]);
            $observer->table('users')->where('id', $liker)->update(['like_count' => new Expression('like_count + 1')]);

            // If the deletion ever waits for the like's uncommitted user row,
            // fail in a second rather than hang the run.
            $db->statement('SET SESSION innodb_lock_wait_timeout = 1');

            $discussions = $db->getQueryGrammar()->wrapTable('discussions');
            $committed = false;
            $db->listen(function (QueryExecuted $query) use ($observer, $discussions, &$committed) {
                if ($committed || ! str_ends_with($query->sql, 'for update') || ! str_contains($query->sql, "from $discussions ")) {
                    return;
                }

                // The deletion has taken its discussion lock; the like
                // commits before the deletion reads the likes.
                $committed = true;
                $observer->commit();
            });

            if ($withSnapshot) {
                try {
                    $this->assertTrue(CommentPost::findOrFail($deletedPost)->delete());
                    $db->commit();
                } catch (DeadlockException $e) {
                    // MariaDB with snapshot isolation rejects a lock on a row
                    // newer than its snapshot. The owner of that transaction
                    // must start it again, since the database rolled it back.
                    $this->assertSame('mariadb', $db->getDriverName());
                    $this->assertStringContainsString('Record has changed since last read', $e->getMessage());
                    $this->closeTransaction($db);
                    $this->assertTrue($observer->table('posts')->where('id', $deletedPost)->exists());
                    $this->assertSame(1, (int) $observer->table('users')->where('id', $liker)->value('like_count'));
                    $this->assertTrue(CommentPost::findOrFail($deletedPost)->delete());
                }
            } else {
                $response = $this->send($this->request('DELETE', "/api/posts/$deletedPost", ['authenticatedAs' => 1]));
                $this->assertSame(204, $response->getStatusCode(), (string) $response->getBody());
            }

            $this->assertTrue($committed, 'The like should have committed inside the deletion.');

            $this->assertFalse($observer->table('posts')->where('id', $deletedPost)->exists());
            $this->assertFalse($observer->table('post_likes')->where('post_id', $deletedPost)->exists());
            $this->assertSame(
                0,
                (int) $observer->table('users')->where('id', $liker)->value('like_count'),
                'The like committed before the count was read, so deleting the post has to remove it from the count.'
            );
        } finally {
            $this->closeTransaction($db);

            // A no-op when the listener already committed it.
            $observer->rollBack();

            $observer->transaction(fn () => $this->removeFixture($observer, $liker, $discussion, null));
            $observer->disconnect();
        }
    }

    public static function deletionTransactions(): array
    {
        return [
            'standalone' => [false],
            'existing repeatable-read snapshot' => [true],
        ];
    }

    #[Test]
    public function a_moved_post_uses_its_current_discussion_for_deletion(): void
    {
        $this->extension('flarum-likes');

        $discussionsLocked = [];
        $locksAtDeletion = [];
        $this->extend((new Event())->listen(DeletingPosts::class, function () use (&$discussionsLocked, &$locksAtDeletion) {
            $locksAtDeletion[] = $discussionsLocked;
        }));

        $db = $this->database();
        $id = random_int(1000000000, 2000000000);
        $deletedPost = $id - 1;
        $destination = $id - 2;
        $db->rollBack();

        try {
            $this->insertFixture($db, $id, $id, $deletedPost, $destination, Carbon::now()->toDateTimeString());
            $post = CommentPost::findOrFail($deletedPost);
            $db->table('posts')->where('id', $deletedPost)->update(['discussion_id' => $destination]);

            $discussions = $db->getQueryGrammar()->wrapTable('discussions');
            $db->listen(function (QueryExecuted $query) use ($discussions, &$discussionsLocked) {
                if (str_contains($query->sql, "from $discussions ") && str_contains($query->sql, ' in (')) {
                    $discussionsLocked[] = (int) $query->bindings[0];
                }
            });

            $this->assertTrue($post->delete());
            $this->assertSame([[$id, $destination]], $locksAtDeletion);
            $this->assertFalse($db->table('posts')->where('id', $deletedPost)->exists());
        } finally {
            $this->closeTransaction($db);
            $this->removeFixture($db, $id, $id, $destination);
        }
    }

    /**
     * A discussion with two posts, one of them liked by the liker, and
     * optionally a second discussion with a post to like meanwhile.
     *
     * @param int $discussion Also the id of its first, kept, post.
     */
    private function insertFixture(Connection $db, int $liker, int $discussion, int $deletedPost, ?int $likedMeanwhile, string $now): void
    {
        $db->table('users')->insert([
            'id' => $liker, 'username' => "likesconflict$liker", 'email' => "likesconflict$liker@example.test",
            'password' => '', 'joined_at' => $now, 'is_email_confirmed' => true, 'like_count' => 1,
        ]);
        $db->table('discussions')->insert([
            'id' => $discussion, 'title' => 'Delete conflict', 'slug' => "delete-conflict-$discussion",
            'user_id' => 1, 'created_at' => $now, 'comment_count' => 2,
        ]);
        $db->table('posts')->insert([
            ['id' => $discussion, 'number' => 1, 'discussion_id' => $discussion, 'user_id' => 1,
                'created_at' => $now, 'type' => 'comment', 'content' => '<t><p>Kept</p></t>'],
            ['id' => $deletedPost, 'number' => 2, 'discussion_id' => $discussion, 'user_id' => 1,
                'created_at' => $now, 'type' => 'comment', 'content' => '<t><p>Deleted</p></t>'],
        ]);
        $db->table('discussions')->where('id', $discussion)->update([
            'first_post_id' => $discussion, 'last_post_id' => $deletedPost, 'last_post_number' => 2,
        ]);
        $db->table('post_likes')->insert(['post_id' => $deletedPost, 'user_id' => $liker]);

        if ($likedMeanwhile !== null) {
            $db->table('discussions')->insert([
                'id' => $likedMeanwhile, 'title' => 'Other discussion', 'slug' => "other-$likedMeanwhile",
                'user_id' => 1, 'created_at' => $now, 'comment_count' => 1,
            ]);
            $db->table('posts')->insert([
                'id' => $likedMeanwhile, 'number' => 1, 'discussion_id' => $likedMeanwhile, 'user_id' => 1,
                'created_at' => $now, 'type' => 'comment', 'content' => '<t><p>Liked meanwhile</p></t>',
            ]);
            $db->table('discussions')->where('id', $likedMeanwhile)->update([
                'first_post_id' => $likedMeanwhile, 'last_post_id' => $likedMeanwhile, 'last_post_number' => 1,
            ]);
        }
    }

    private function removeFixture(Connection $db, int $liker, int $discussion, ?int $likedMeanwhile): void
    {
        $discussions = array_filter([$discussion, $likedMeanwhile]);

        $db->table('post_likes')->where('user_id', $liker)->delete();
        $db->table('posts')->whereIn('discussion_id', $discussions)->delete();
        $db->table('discussions')->whereIn('id', $discussions)->delete();
        $db->table('users')->where('id', $liker)->delete();
    }

    /** A failed request can leave its own transaction open; release it before cleanup. */
    private function closeTransaction(Connection $db): void
    {
        while ($db->transactionLevel() > 0) {
            $db->rollBack();
        }
    }
}
