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
use Illuminate\Database\Events\QueryExecuted;
use PHPUnit\Framework\Attributes\Test;

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
            ],
        ]);
    }

    #[Test]
    public function a_discussion_cascade_delivers_its_locked_posts(): void
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

        $this->assertSame(4, Post::query()->count());
    }

    #[Test]
    public function returning_false_skips_the_deletion_callbacks(): void
    {
        $callbacks = 0;
        $this->extend((new Extend\Event())->listen(DeletingPosts::class, function (DeletingPosts $event) use (&$callbacks) {
            $event->afterDeletion(function () use (&$callbacks) {
                $callbacks++;
            });
        }));

        $this->database();

        $delivered = null;
        $result = Post::deleteWithEvents(Post::query()->where('discussion_id', 1), function ($posts) use (&$delivered) {
            $delivered = array_map('intval', $posts->pluck('posts.id')->all());

            return false;
        });

        $this->assertFalse($result);
        $this->assertSame([1, 2, 3], $delivered, 'The delete callback should receive the locked posts.');
        $this->assertSame(0, $callbacks, 'A cancelled deletion must not run the deletion callbacks.');
        $this->assertSame(3, Post::query()->where('discussion_id', 1)->count());
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
    public function deleting_a_large_discussion_reads_the_posts_in_bounded_chunks(): void
    {
        $db = $this->database();
        $now = Carbon::now()->toDateTimeString();

        $rows = [];
        for ($number = 4; $number <= 20003; $number++) {
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

        $this->assertGreaterThan(1, count($reads), 'The post ids should be read in more than one piece.');
        foreach ($reads as $sql) {
            $this->assertStringContainsString('limit', strtolower($sql), "A post read should be bounded: $sql");
        }
        $this->assertSame(0, Post::query()->where('discussion_id', 1)->count());
    }
}
