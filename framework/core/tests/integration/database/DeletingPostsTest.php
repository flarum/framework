<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\database;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Post\Event\DeletingPosts;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Container\Container;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\Test;
use Throwable;

class DeletingPostsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 3, 'last_post_number' => 3],
                ['id' => 2, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 4, 'comment_count' => 1, 'last_post_number' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>one</p></t>'],
                ['id' => 2, 'discussion_id' => 1, 'number' => 2, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>two</p></t>'],
                ['id' => 3, 'discussion_id' => 1, 'number' => 3, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>three</p></t>'],
                ['id' => 4, 'discussion_id' => 2, 'number' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>other</p></t>'],
                // Its discussion row is missing, as after an interrupted cascade.
                ['id' => 5, 'discussion_id' => 99, 'number' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>orphan</p></t>'],
            ],
        ]);
    }

    #[Test]
    public function a_discussion_cascade_delivers_its_posts(): void
    {
        $delivered = null;
        $presentDuringDispatch = false;

        $this->extend((new Extend\Event())->listen(DeletingPosts::class, function (DeletingPosts $event) use (&$delivered, &$presentDuringDispatch) {
            $delivered = array_map('intval', (clone $event->posts())->orderBy('posts.id')->pluck('posts.id')->all());
            $presentDuringDispatch = $event->posts()->exists();
        }));

        $this->database();

        Discussion::query()->findOrFail(1)->delete();

        $this->assertSame([1, 2, 3], $delivered, 'A discussion cascade delivers exactly its own posts.');
        $this->assertTrue($presentDuringDispatch, 'The posts are still there while the listeners run.');
        $this->assertSame(0, Post::query()->where('discussion_id', 1)->count());
        $this->assertSame(1, Post::query()->where('discussion_id', 2)->count());
    }

    #[Test]
    public function unsupported_builders_are_rejected(): void
    {
        $this->database();

        $shapes = [
            'limit' => fn ($query) => $query->limit(1),
            'offset' => fn ($query) => $query->offset(1),
            'order' => fn ($query) => $query->orderByDesc('id'),
            'join' => fn ($query) => $query->join('discussions', 'discussions.id', '=', 'posts.discussion_id'),
            'group' => fn ($query) => $query->groupBy('discussion_id'),
            'distinct' => fn ($query) => $query->distinct(),
        ];

        foreach ($shapes as $shape => $apply) {
            $deleted = false;

            try {
                Post::deleteWithEvents($apply(Post::query()), function () use (&$deleted) {
                    $deleted = true;
                });
                $this->fail("Expected the $shape query to be rejected");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('unpaginated', $e->getMessage(), $shape);
            }

            $this->assertFalse($deleted, "A rejected $shape query must not delete anything.");
        }

        $this->assertSame(5, Post::query()->count());
    }

    #[Test]
    public function a_post_whose_discussion_is_gone_is_still_deleted(): void
    {
        $delivered = null;
        $presentDuringDispatch = false;

        $this->extend((new Extend\Event())->listen(DeletingPosts::class, function (DeletingPosts $event) use (&$delivered, &$presentDuringDispatch) {
            $delivered = array_map('intval', (clone $event->posts())->orderBy('posts.id')->pluck('posts.id')->all());
            $presentDuringDispatch = $event->posts()->exists();
        }));

        $this->database();

        $this->assertTrue(Post::query()->findOrFail(5)->delete(), 'The post should be deleted without its discussion.');
        $this->assertFalse(Post::query()->whereKey(5)->exists());
        $this->assertSame(4, Post::query()->count(), 'The posts of the other discussions should be untouched.');
        $this->assertSame([5], $delivered, 'Deletion listeners should be handed the post whose discussion is gone.');
        $this->assertTrue($presentDuringDispatch, 'The post is still there while the listeners run.');
    }

    #[Test]
    public function returning_false_skips_the_deletion_callbacks(): void
    {
        $callbacks = 0;
        $this->extend((new Extend\Event())->listen(DeletingPosts::class, function (DeletingPosts $event) use (&$callbacks) {
            $event->afterDeletion(function () use (&$callbacks) {
                $callbacks++;
            });

            $event->connection->table('users')->where('id', 2)->update(['username' => 'changed']);
        }));

        $this->database();

        $delivered = null;
        $result = Post::deleteWithEvents(Post::query()->where('discussion_id', 1), function ($posts) use (&$delivered) {
            $delivered = array_map('intval', $posts->pluck('posts.id')->all());

            return false;
        });

        $this->assertFalse($result);
        $this->assertSame([1, 2, 3], $delivered, 'The delete callback should receive the posts the deletion selected.');
        $this->assertSame(0, $callbacks, 'A cancelled deletion must not run the deletion callbacks.');
        $this->assertSame('normal', User::query()->findOrFail(2)->username, 'What the listeners wrote should be rolled back.');
        $this->assertSame(3, Post::query()->where('discussion_id', 1)->count());
    }

    #[Test]
    public function a_filter_without_discussions_only_deletes_from_the_discussions_it_locked(): void
    {
        // A matching post turns up in another discussion once the first has
        // been locked.
        $this->extend((new Extend\Event())->listen(DeletingPosts::class, function (DeletingPosts $event) {
            $event->connection->table('posts')->insert(['id' => 6, 'discussion_id' => 2, 'number' => 2, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>marked</p></t>']);
        }));

        $this->database();
        Post::query()->whereKey(1)->update(['content' => '<t><p>marked</p></t>']);

        Post::deleteWithEvents(Post::query()->where('content', 'like', '%marked%'), fn ($posts) => $posts->delete());

        $this->assertFalse(Post::query()->whereKey(1)->exists());
        $this->assertTrue(Post::query()->whereKey(6)->exists(), 'A post in a discussion that was not locked must be left alone.');
    }

    #[Test]
    public function deleting_a_discussion_that_is_already_gone_raises_nothing(): void
    {
        $this->database();

        $discussion = Discussion::query()->findOrFail(2);
        $this->database()->table('discussions')->where('id', 2)->delete();

        $discussion->delete();

        $this->assertSame([], $discussion->releaseEvents());
    }

    #[Test]
    public function a_failure_after_the_deletion_rolls_the_whole_cascade_back(): void
    {
        $this->extend((new Extend\Event())->listen(DeletingPosts::class, function (DeletingPosts $event) {
            $event->afterDeletion(function () use ($event) {
                $event->connection->table('users')->where('id', 2)->update(['username' => 'changed']);

                throw new \RuntimeException('counter update failed');
            });
        }));

        $this->database();

        try {
            Discussion::query()->findOrFail(1)->delete();
            $this->fail('Expected the deletion to fail');
        } catch (\RuntimeException $e) {
            $this->assertSame('counter update failed', $e->getMessage());
        }

        $this->assertTrue(Discussion::query()->whereKey(1)->exists(), 'The discussion should be back.');
        $this->assertSame(3, Post::query()->where('discussion_id', 1)->count(), 'The posts should be back.');
        $this->assertSame('normal', User::query()->findOrFail(2)->username, 'The listener work should be rolled back too.');
    }

    #[Test]
    public function deleting_a_large_discussion_does_not_read_its_posts(): void
    {
        $db = $this->database();
        $now = Carbon::now()->toDateTimeString();

        $rows = [];
        for ($number = 4; $number <= 2003; $number++) {
            $rows[] = [
                'discussion_id' => 1,
                'number' => $number,
                'created_at' => $now,
                'user_id' => 2,
                'type' => 'comment',
                'content' => '<t><p>filler</p></t>',
            ];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            $db->table('posts')->insert($chunk);
        }

        $table = $db->getQueryGrammar()->wrapTable('posts');
        $idColumn = $db->getQueryGrammar()->wrap('posts.id');
        $reads = [];
        $db->listen(function (QueryExecuted $query) use ($table, $idColumn, &$reads) {
            if (str_starts_with($query->sql, 'select') && str_contains($query->sql, "from $table") && str_contains($query->sql, $idColumn)) {
                $reads[] = $query->sql;
            }
        });

        Discussion::query()->findOrFail(1)->delete();

        $this->assertSame([], $reads, 'The posts are deleted from one query; their ids are not read into PHP.');
        $this->assertSame(0, Post::query()->where('discussion_id', 1)->count());
    }

    /**
     * A deletion locks its discussion, not the post rows: while it runs, a
     * second connection can write both a post of another discussion and one of
     * the posts being deleted.
     */
    #[Test]
    public function a_delete_does_not_lock_post_rows(): void
    {
        $targetPost = null;
        $otherPost = null;
        $observer = null;
        $probes = [];

        // Registered before the app boots: the probes run while the deletion
        // holds whatever locks it takes.
        $this->extend((new Extend\Event())->listen(DeletingPosts::class, function () use (&$observer, &$targetPost, &$otherPost, &$probes) {
            if ($probes !== []) {
                return;
            }

            foreach (['target' => $targetPost, 'other' => $otherPost] as $name => $postId) {
                try {
                    $observer->transaction(fn () => $observer->table('posts')->where('id', $postId)
                        ->update(['content' => '<t><p>edited</p></t>']));
                    $probes[$name] = null;
                } catch (QueryException $e) {
                    $probes[$name] = $e;
                }
            }
        }));

        $db = $this->database();
        $driver = $db->getDriverName();

        if (! in_array($driver, ['pgsql', 'mysql', 'mariadb'], true)) {
            $this->markTestSkipped('The independent lock probe needs row locks, which '.$driver.' does not have.');
        }

        $now = Carbon::now()->toDateTimeString();
        $id = random_int(1000000000, 2000000000);
        // The other discussion's post sits between the two posts of the
        // discussion that is deleted.
        $otherPost = $id + 1;
        $targetPost = $id + 2;
        $observer = (new ConnectionFactory(new Container()))->make($db->getConfig());

        if ($driver === 'pgsql') {
            $observer->statement("SET lock_timeout = '1s'");
        } else {
            // A locked row is proven by the probe timing out, since MySQL 5.7
            // has no FOR UPDATE NOWAIT.
            $observer->statement('SET SESSION innodb_lock_wait_timeout = 1');
        }

        try {
            // Commit the fixture on the observer so both sessions can see it.
            $observer->transaction(function () use ($observer, $id, $now) {
                $observer->table('discussions')->insert([
                    ['id' => $id, 'title' => 'Deleted', 'slug' => "deleted-$id", 'user_id' => 1, 'created_at' => $now, 'comment_count' => 2, 'last_post_number' => 3],
                    ['id' => $id + 1, 'title' => 'Other', 'slug' => "other-$id", 'user_id' => 1, 'created_at' => $now, 'comment_count' => 1, 'last_post_number' => 1],
                ]);
                $observer->table('posts')->insert([
                    ['id' => $id, 'number' => 1, 'discussion_id' => $id, 'user_id' => 1, 'created_at' => $now,
                        'type' => 'comment', 'content' => '<t><p>one</p></t>'],
                    ['id' => $id + 1, 'number' => 1, 'discussion_id' => $id + 1, 'user_id' => 1, 'created_at' => $now,
                        'type' => 'comment', 'content' => '<t><p>other</p></t>'],
                    ['id' => $id + 2, 'number' => 3, 'discussion_id' => $id, 'user_id' => 1, 'created_at' => $now,
                        'type' => 'comment', 'content' => '<t><p>three</p></t>'],
                ]);
                $observer->table('discussions')->where('id', $id)->update(['first_post_id' => $id, 'last_post_id' => $id + 2]);
                $observer->table('discussions')->where('id', $id + 1)->update(['first_post_id' => $id + 1, 'last_post_id' => $id + 1]);
            });

            // The fixture of setUp() is in the test's transaction; the
            // deletion has to run in its own for the probes to see its locks.
            $db->rollBack();

            Discussion::query()->findOrFail($id)->delete();

            $this->assertSame(['target', 'other'], array_keys($probes), 'The deletion should dispatch DeletingPosts.');

            foreach ($probes as $name => $probe) {
                $this->assertFalse(
                    $probe instanceof Throwable,
                    "$name post must not be locked: ".($probe instanceof Throwable ? $probe->getMessage() : '')
                );
            }

            $this->assertTrue(Post::query()->whereKey($otherPost)->exists(), 'The other discussion\'s post survives.');
        } finally {
            while ($db->transactionLevel() > 0) {
                $db->rollBack();
            }

            $observer->table('posts')->whereIn('discussion_id', [$id, $id + 1])->delete();
            $observer->table('discussions')->whereIn('id', [$id, $id + 1])->delete();
            $observer->disconnect();

            $db->beginTransaction();
        }
    }

    /**
     * The discussion lock does not read posts. On MySQL and MariaDB that read
     * would create the transaction's read view before the lock is granted.
     */
    #[Test]
    public function the_discussion_lock_reads_no_posts(): void
    {
        $db = $this->database();
        $posts = $db->getQueryGrammar()->wrapTable('posts');
        $discussions = $db->getQueryGrammar()->wrapTable('discussions');
        $locks = [];

        $db->listen(function (QueryExecuted $query) use ($discussions, &$locks) {
            $from = strstr($query->sql, ' from ');
            if ($from !== false && str_starts_with($from, " from $discussions ")) {
                $locks[] = $query->sql;
            }
        });

        Post::query()->findOrFail(1)->delete();

        $this->assertNotSame([], $locks, 'The deletion should lock its discussion.');
        $this->assertStringNotContainsString(
            "from $posts",
            $locks[0],
            'The discussion lock must not read posts: that read can create the transaction\'s read view before the lock is granted.'
        );
    }
}
