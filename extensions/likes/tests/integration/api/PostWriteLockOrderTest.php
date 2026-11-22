<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\Testing\integration\TestCase;
use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class PostWriteLockOrderTest extends TestCase
{
    #[Test]
    #[DataProvider('postWrites')]
    public function a_post_write_locks_its_discussion_before_the_post_and_user(string $action): void
    {
        $this->extension('flarum-likes', 'flarum-gdpr');
        $this->setting('flarum-gdpr.allow-deletion', true);
        $this->setting('mail_driver', 'log');
        $db = $this->database();
        $driver = $db->getDriverName();
        if ($driver !== 'pgsql' && $driver !== 'mysql') {
            $this->markTestSkipped('The independent lock probe needs row locks, which '.$driver.' does not have.');
        }

        $observer = (new ConnectionFactory(new Container()))->make($db->getConfig());
        $id = random_int(1000000000, 2000000000);
        $now = Carbon::now();

        if ($driver === 'mysql') {
            // A locked row is proven by the probe timing out, since MySQL 5.7
            // has no FOR UPDATE NOWAIT. One second is the shortest wait.
            $observer->statement('SET SESSION innodb_lock_wait_timeout = 1');
        }

        try {
            // Commit the fixture on the observer connection so both sessions can see it.
            $observer->transaction(function () use ($observer, $id, $now) {
                $observer->table('users')->insert([
                    'id' => $id, 'username' => "likeslock$id", 'email' => "likeslock$id@example.test",
                    'password' => '', 'joined_at' => $now, 'is_email_confirmed' => true, 'like_count' => 1,
                ]);
                $observer->table('discussions')->insert([
                    'id' => $id, 'title' => 'Like counter lock order', 'slug' => "likes-lock-$id", 'user_id' => $id, 'created_at' => $now,
                ]);
                $observer->table('posts')->insert([
                    'id' => $id - 1, 'number' => 1, 'discussion_id' => $id, 'user_id' => 1, 'created_at' => $now,
                    'type' => 'comment', 'content' => '<t><p>First post</p></t>',
                ]);
                $observer->table('posts')->insert([
                    'id' => $id, 'number' => 2, 'discussion_id' => $id, 'user_id' => $id, 'created_at' => $now,
                    'type' => 'comment', 'content' => '<t><p>Comment</p></t>',
                ]);
                $observer->table('discussions')->where('id', $id)->update([
                    'first_post_id' => $id - 1, 'last_post_id' => $id, 'last_post_number' => 2, 'comment_count' => 2,
                ]);
                $observer->table('post_likes')->insert(['post_id' => $id, 'user_id' => $id]);
                $observer->table('gdpr_erasure')->insert([
                    'id' => $id, 'user_id' => $id, 'status' => ErasureRequest::STATUS_USER_CONFIRMED,
                    'created_at' => $now, 'user_confirmed_at' => $now,
                ]);
            });

            $grammar = $db->getQueryGrammar();
            $posts = $grammar->wrapTable('posts');
            $users = $grammar->wrapTable('users');
            $postWasLocked = $userWasLocked = false;
            $db->listen(function (QueryExecuted $query) use ($observer, $posts, $users, $id, &$postWasLocked, &$userWasLocked) {
                if (! str_ends_with($query->sql, 'for update')) {
                    return;
                }

                $from = strstr($query->sql, ' from ');
                if (! $postWasLocked && str_starts_with($from ?: '', " from $posts ")) {
                    $this->assertLocked($observer, 'discussions', $id);
                    $postWasLocked = true;
                }

                if (! $userWasLocked && str_starts_with($from ?: '', " from $users ")) {
                    $this->assertTrue($postWasLocked, 'The post should be locked before its liker.');
                    $this->assertLocked($observer, 'posts', $id);
                    $userWasLocked = true;
                }
            });

            [$method, $route, $actor, $attributes, $status] = match ($action) {
                'unlike' => ['PATCH', "/api/posts/$id", $id, ['isLiked' => false], 200],
                'post' => ['DELETE', "/api/posts/$id", 1, [], 204],
                'discussion' => ['DELETE', "/api/discussions/$id", 1, [], 204],
                'erasure' => ['PATCH', "/api/user-erasure-requests/$id", 1, ['processedMode' => ErasureRequest::MODE_DELETION], 200],
            };
            $response = $this->send($this->request($method, $route, [
                'authenticatedAs' => $actor,
                'json' => ['data' => ['attributes' => $attributes]],
            ]));

            $this->assertSame($status, $response->getStatusCode(), (string) $response->getBody());
            $this->assertTrue($postWasLocked);
            $this->assertTrue($userWasLocked);
        } finally {
            $db->rollBack();

            try {
                $observer->table('gdpr_erasure')->where('id', $id)->delete();
                $observer->table('discussions')->where('id', $id)->delete();
                $observer->table('users')->where('id', $id)->delete();
            } finally {
                $observer->disconnect();
                $db->beginTransaction();
            }
        }
    }

    private function assertLocked(Connection $observer, string $table, int $id): void
    {
        $driver = $observer->getDriverName();

        try {
            $observer->transaction(fn () => $observer->table($table)->where('id', $id)
                ->lock($driver === 'pgsql' ? 'for update nowait' : 'for update')->first());
        } catch (QueryException $e) {
            if ($driver === 'pgsql') {
                $this->assertSame('55P03', $e->errorInfo[0] ?? null, $e->getMessage());
            } else {
                // 1205: the second session waited out innodb_lock_wait_timeout.
                $this->assertSame(1205, $e->errorInfo[1] ?? null, $e->getMessage());
            }

            return;
        }

        $this->fail("$table was not locked by the first connection.");
    }

    public static function postWrites(): iterable
    {
        foreach (['unlike', 'post', 'discussion', 'erasure'] as $action) {
            yield $action => [$action];
        }
    }
}
