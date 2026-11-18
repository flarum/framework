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
use Flarum\Gdpr\Data\Posts as GdprPosts;
use Flarum\Gdpr\Extend\UserData;
use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Database\Events\QueryExecuted;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class UserLikeCountTest extends TestCase
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
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 3, 'first_post_id' => 1, 'comment_count' => 3, 'last_post_number' => 3],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>one</p></t>'],
                ['id' => 2, 'number' => 2, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>two</p></t>'],
                ['id' => 3, 'number' => 3, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>three</p></t>', 'is_private' => true],
            ],
        ]);
    }

    #[Test]
    public function liking_and_unliking_updates_the_users_like_count()
    {
        $this->like(1, 2);
        $this->like(2, 2);

        $this->assertSame(2, $this->likeCountOf(2));

        $this->like(1, 2, false);

        $this->assertSame(1, $this->likeCountOf(2));
    }

    #[Test]
    public function like_count_is_shown_on_the_user()
    {
        $this->like(1, 2);

        $response = $this->send(
            $this->request('GET', '/api/users/2', ['authenticatedAs' => 3])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame(1, json_decode($response->getBody(), true)['data']['attributes']['likeCount']);
    }

    #[Test]
    public function likes_of_private_posts_are_not_counted()
    {
        $this->database()->table('post_likes')->insert([
            ['post_id' => 3, 'user_id' => 2],
        ]);

        $this->like(1, 2);

        $this->assertSame(1, $this->likeCountOf(2));
    }

    #[Test]
    public function deleting_a_post_updates_the_like_count_of_everyone_who_liked_it()
    {
        $this->like(2, 2);
        $this->assertSame(1, $this->likeCountOf(2));

        $response = $this->send(
            $this->request('DELETE', '/api/posts/2', ['authenticatedAs' => 1])
        );

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(0, $this->likeCountOf(2));
    }

    #[Test]
    public function a_post_delete_locks_the_affected_counter_before_removing_the_post()
    {
        $this->like(2, 2);
        $db = $this->database();
        $outsideLevel = $db->transactionLevel();
        $grammar = $db->getQueryGrammar();
        $users = $grammar->wrapTable('users');
        $posts = $grammar->wrapTable('posts');
        $hadUserLock = false;
        $protectedAtDelete = false;

        $db->listen(function (QueryExecuted $query) use ($db, $outsideLevel, $users, $posts, &$hadUserLock, &$protectedAtDelete) {
            if (str_contains($query->sql, "from $users") && str_ends_with($query->sql, 'for update')) {
                $hadUserLock = true;
            }

            if (str_starts_with($query->sql, "delete from $posts")) {
                // SQLite protects writes at the database level.
                $protectedAtDelete = $db->transactionLevel() > $outsideLevel && ($hadUserLock || $db->getDriverName() === 'sqlite');
            }
        });

        $response = $this->send($this->request('DELETE', '/api/posts/2', ['authenticatedAs' => 1]));

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertTrue($protectedAtDelete);
        $this->assertSame(0, $this->likeCountOf(2));
    }

    #[Test]
    public function deleting_a_post_updates_every_liker_and_leaves_other_likes_alone()
    {
        $this->database()->table('post_likes')->insert([
            ['post_id' => 1, 'user_id' => 2],
            ['post_id' => 2, 'user_id' => 2],
            ['post_id' => 2, 'user_id' => 4],
            ['post_id' => 1, 'user_id' => 3],
        ]);
        foreach ([2 => 2, 3 => 1, 4 => 1] as $id => $count) {
            $this->database()->table('users')->where('id', $id)->update(['like_count' => $count]);
        }

        $response = $this->send(
            $this->request('DELETE', '/api/posts/2', ['authenticatedAs' => 1])
        );

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(1, $this->likeCountOf(2));
        $this->assertSame(0, $this->likeCountOf(4));
        // Not a liker of the deleted post, so not recounted.
        $this->assertSame(1, $this->likeCountOf(3));
    }

    #[Test]
    public function deleting_one_post_preserves_a_likers_large_history()
    {
        $posts = [];
        $likes = [];
        for ($id = 10; $id < 1010; $id++) {
            $posts[] = ['id' => $id, 'number' => $id, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>history</p></t>'];
            $likes[] = ['post_id' => $id, 'user_id' => 2];
        }
        foreach (array_chunk($posts, 100) as $chunk) {
            $this->database()->table('posts')->insert($chunk);
        }
        foreach (array_chunk($likes, 100) as $chunk) {
            $this->database()->table('post_likes')->insert($chunk);
        }
        $this->like(2, 2);
        $this->assertSame(1001, $this->likeCountOf(2));

        $response = $this->send($this->request('DELETE', '/api/posts/2', ['authenticatedAs' => 1]));
        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(1000, $this->likeCountOf(2));
        $this->assertSame(1000, $this->database()->table('post_likes')->where('user_id', 2)->count());
    }

    #[Test]
    public function deleting_a_private_post_does_not_decrement_other_likes()
    {
        $this->like(1, 2);
        $this->database()->table('post_likes')->insert(['post_id' => 3, 'user_id' => 2]);
        Post::query()->findOrFail(3)->delete();
        $this->assertFalse(Post::query()->whereKey(3)->exists());
        $this->assertSame(1, $this->likeCountOf(2));
    }

    #[Test]
    public function deleting_a_post_does_not_make_a_zero_counter_negative()
    {
        $this->like(2, 2);
        $this->database()->table('users')->where('id', 2)->update(['like_count' => 0]);
        $response = $this->send($this->request('DELETE', '/api/posts/2', ['authenticatedAs' => 1]));
        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(0, $this->likeCountOf(2));
    }

    #[Test]
    public function repeated_likes_and_unlikes_are_idempotent()
    {
        $this->like(1, 2);
        $this->like(1, 2);
        $this->assertSame(1, $this->likeCountOf(2));
        $this->like(1, 2, false);
        $this->like(1, 2, false);
        $this->assertSame(0, $this->likeCountOf(2));
    }

    #[Test]
    public function non_comment_likes_do_not_contribute()
    {
        $this->database()->table('posts')->where('id', 2)->update(['type' => 'discussionRenamed', 'content' => '["old","new"]']);
        $this->like(2, 2);
        $this->assertSame(0, $this->likeCountOf(2));
    }

    #[Test]
    public function approving_a_liked_post_updates_the_count()
    {
        $this->extension('flarum-approval', 'flarum-flags');
        $this->database()->table('posts')->where('id', 3)->update(['is_approved' => false]);
        $this->like(3, 1);
        $this->assertSame(0, $this->likeCountOf(1));
        $response = $this->send($this->request('PATCH', '/api/posts/3', [
            'authenticatedAs' => 1,
            'json' => ['data' => ['attributes' => ['isApproved' => true]]],
        ]));
        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(1, $this->likeCountOf(1));
    }

    #[Test]
    public function deleting_a_discussion_updates_counts()
    {
        $this->like(1, 2);
        $this->like(2, 2);
        $response = $this->send($this->request('DELETE', '/api/discussions/1', ['authenticatedAs' => 1]));
        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(0, $this->likeCountOf(2));
    }

    #[Test]
    public function direct_model_deletion_updates_counts_and_rollback_restores_them()
    {
        $this->like(2, 2);
        $db = $this->database();
        $db->beginTransaction();
        Post::query()->findOrFail(2)->delete();
        $this->assertSame(0, $this->likeCountOf(2));
        $db->rollBack();
        $this->assertSame(1, $this->likeCountOf(2));
        Post::query()->findOrFail(2)->delete();
        $this->assertSame(0, $this->likeCountOf(2));
    }

    #[Test]
    public function a_failed_delete_rolls_back_the_post_and_its_likes()
    {
        $this->like(2, 2);
        $events = $this->app()->getContainer()->make(\Illuminate\Contracts\Events\Dispatcher::class);
        $events->listen('eloquent.deleted: '.\Flarum\Post\CommentPost::class, function () {
            throw new \RuntimeException('delete failed');
        });

        try {
            Post::query()->findOrFail(2)->delete();
            $this->fail('Expected deletion to fail');
        } catch (\RuntimeException $e) {
            $this->assertSame('delete failed', $e->getMessage());
        }
        $this->assertNotNull(Post::query()->find(2));
        $this->assertSame(1, $this->likeCountOf(2));
        $this->assertTrue($this->database()->table('post_likes')->where('post_id', 2)->where('user_id', 2)->exists());
    }

    #[Test]
    #[DataProvider('gdprPostPolicies')]
    public function gdpr_erasure_respects_the_posts_policy_and_updates_counts(bool $retainPosts)
    {
        $this->extension('flarum-gdpr');
        $this->setting('flarum-gdpr.allow-deletion', true);
        $this->setting('mail_driver', 'log');
        if ($retainPosts) {
            $this->extend((new UserData())->removeType(GdprPosts::class));
        }
        $this->prepareDatabase([
            'gdpr_erasure' => [
                [
                    'id' => 1,
                    'user_id' => 3,
                    'verification_token' => 'likes-erasure-test',
                    'status' => ErasureRequest::STATUS_USER_CONFIRMED,
                    'created_at' => Carbon::now(),
                    'user_confirmed_at' => Carbon::now(),
                ],
            ],
        ]);

        $this->like(1, 2);
        $this->like(2, 2);

        $response = $this->send($this->request('PATCH', '/api/user-erasure-requests/1', [
            'authenticatedAs' => 1,
            'json' => ['data' => ['attributes' => ['processedMode' => ErasureRequest::MODE_DELETION]]],
        ]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNull(User::query()->find(3));
        $this->assertSame($retainPosts, Post::query()->whereKey(1)->exists());
        $this->assertSame($retainPosts ? 2 : 0, $this->likeCountOf(2));
    }

    public static function gdprPostPolicies(): array
    {
        return ['delete posts' => [false], 'retain posts' => [true]];
    }

    #[Test]
    public function a_recount_includes_direct_pivot_inserts()
    {
        $this->database()->table('post_likes')->insert(['post_id' => 1, 'user_id' => 2]);
        $this->like(2, 2);
        $this->assertSame(2, $this->likeCountOf(2));
    }

    #[Test]
    public function refreshing_the_actor_does_not_leave_a_dirty_counter()
    {
        $seen = false;
        $this->extend((new \Flarum\Extend\Event())->listen(\Flarum\Likes\Event\PostWasLiked::class, function ($event) use (&$seen) {
            $seen = true;
            $this->assertFalse($event->user->isDirty('like_count'));
            $this->database()->table('users')->where('id', $event->user->id)->update(['like_count' => 2]);
            $event->user->last_seen_at = Carbon::now();
            $event->user->save();
            $this->assertSame(2, $this->likeCountOf($event->user->id));
        }));
        $this->like(1, 2);
        $this->assertTrue($seen);
    }

    protected function like(int $postId, int $userId, bool $liked = true): void
    {
        $response = $this->send(
            $this->request('PATCH', "/api/posts/$postId", [
                'authenticatedAs' => $userId,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'isLiked' => $liked,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    protected function likeCountOf(int $userId): int
    {
        return (int) User::query()->findOrFail($userId)->like_count;
    }
}
