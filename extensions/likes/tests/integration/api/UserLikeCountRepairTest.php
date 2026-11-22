<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Extension\ExtensionManager;
use Flarum\Likes\LikeCount;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Database\Events\QueryExecuted;
use PHPUnit\Framework\Attributes\Test;

class UserLikeCountRepairTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-likes');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'acme', 'email' => 'acme@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'liker4', 'email' => 'liker4@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 3, 'first_post_id' => 1, 'comment_count' => 2, 'last_post_number' => 2],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>one</p></t>'],
                ['id' => 2, 'number' => 2, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>two</p></t>'],
            ],
        ]);
    }

    #[Test]
    public function a_repair_only_writes_users_whose_stored_count_differs()
    {
        $db = $this->database();
        $db->table('post_likes')->insert([
            ['post_id' => 1, 'user_id' => 2],
            ['post_id' => 1, 'user_id' => 3],
            ['post_id' => 2, 'user_id' => 3],
        ]);
        // 1 has no likes and a right count, 2 is right, 3 is stale, 4 has no
        // likes but a stale count.
        $db->table('users')->where('id', 1)->update(['like_count' => 0]);
        $db->table('users')->where('id', 2)->update(['like_count' => 1]);
        $db->table('users')->where('id', 3)->update(['like_count' => 9]);
        $db->table('users')->where('id', 4)->update(['like_count' => 3]);

        $untouched = $this->userRows([1, 2]);

        $updates = $this->userUpdates(function () {
            $this->enableLikes();
        });

        // One statement for the chunk, not one per user, and only the stale
        // users are in it.
        $this->assertCount(1, $updates);
        $this->assertStringContainsString('<>', $updates[0]->sql);
        $ids = array_map('strval', $updates[0]->bindings);
        $this->assertContains('3', $ids);
        $this->assertContains('4', $ids);
        $this->assertNotContains('1', $ids);
        $this->assertNotContains('2', $ids);

        $this->assertSame([0, 1, 2, 0], $this->likeCounts());
        $this->assertSame($untouched, $this->userRows([1, 2]), 'A user whose count is already right must not be written.');
    }

    #[Test]
    public function a_repair_with_nothing_stale_writes_nothing()
    {
        $db = $this->database();
        $db->table('post_likes')->insert([['post_id' => 1, 'user_id' => 2]]);
        $db->table('users')->where('id', 2)->update(['like_count' => 1]);

        $before = $this->userRows([1, 2, 3, 4]);

        $updates = $this->userUpdates(function () {
            $this->enableLikes();

            // The repair reports nothing to do as well.
            $this->assertSame(0, $this->app()->getContainer()->make(LikeCount::class)->refreshAll());
        });

        $this->assertSame([], $updates, 'A forum whose counts are already right must not be written.');
        $this->assertSame($before, $this->userRows([1, 2, 3, 4]));
    }

    #[Test]
    public function an_interrupted_repair_is_finished_when_likes_is_enabled_again()
    {
        $db = $this->database();

        $users = [];
        for ($id = 10; $id <= 1009; $id++) {
            $users[] = ['id' => $id, 'username' => "user$id", 'email' => "user$id@machine.local", 'password' => 'x'];
        }
        foreach (array_chunk($users, 500) as $chunk) {
            $db->table('users')->insert($chunk);
        }

        // One stale user in each of the two chunks of 1000 user ids.
        $db->table('post_likes')->insert([
            ['post_id' => 1, 'user_id' => 10],
            ['post_id' => 1, 'user_id' => 1009],
        ]);

        $grammar = $db->getQueryGrammar();
        $usersTable = $grammar->wrapTable('users');
        $updates = 0;

        $db->listen(function (QueryExecuted $query) use ($usersTable, &$updates) {
            if (str_starts_with($query->sql, "update $usersTable ") && ++$updates === 2) {
                throw new \RuntimeException('interrupted');
            }
        });

        try {
            $this->app()->getContainer()->make(LikeCount::class)->refreshAll();
            $this->fail('Expected the second chunk to be interrupted');
        } catch (\RuntimeException $e) {
            $this->assertSame('interrupted', $e->getMessage());
        }

        // The first chunk committed, the interrupted one rolled back.
        $this->assertSame(1, $this->likeCountOf(10));
        $this->assertSame(0, $this->likeCountOf(1009));

        // Enabling again finishes the job without a fresh start.
        $this->enableLikes();

        $this->assertSame(1, $this->likeCountOf(10));
        $this->assertSame(1, $this->likeCountOf(1009));
        $this->assertSame(0, $this->app()->getContainer()->make(LikeCount::class)->refreshAll());
    }

    protected function enableLikes(): void
    {
        $container = $this->app()->getContainer();
        $container->make(ExtensionManager::class)->getExtension('flarum-likes')->enable($container);
    }

    /**
     * Run $callback and return the queries that updated users.
     *
     * @return QueryExecuted[]
     */
    protected function userUpdates(callable $callback): array
    {
        $grammar = $this->database()->getQueryGrammar();
        $users = $grammar->wrapTable('users');
        $updates = [];

        $this->database()->listen(function (QueryExecuted $query) use ($users, &$updates) {
            if (str_starts_with($query->sql, "update $users ")) {
                $updates[] = $query;
            }
        });

        $callback();

        return $updates;
    }

    /**
     * @param int[] $ids
     * @return array<int, array<string, mixed>>
     */
    protected function userRows(array $ids): array
    {
        return $this->database()->table('users')->whereIn('id', $ids)->orderBy('id')
            ->get()
            ->map(fn ($user) => (array) $user)
            ->all();
    }

    /**
     * @return int[]
     */
    protected function likeCounts(): array
    {
        return array_values(array_map(
            'intval',
            $this->database()->table('users')->orderBy('id')->pluck('like_count', 'id')->all()
        ));
    }

    protected function likeCountOf(int $userId): int
    {
        return (int) $this->database()->table('users')->where('id', $userId)->value('like_count');
    }
}
