<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\posts;

use Carbon\Carbon;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\PostResource;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\UserState;
use Flarum\Extend;
use Flarum\Post\CommentPost;
use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\DeletingPosts;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class DeleteTest extends TestCase
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
                ['id' => 3, 'username' => 'acme', 'email' => 'acme@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'acme2', 'email' => 'acme2@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 3, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 5, 'comment_count' => 5, 'last_post_number' => 5, 'last_post_id' => 10],
            ],
            Post::class => [
                ['id' => 5, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(1975, 5, 21)->addMinutes(2)->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 1],
                ['id' => 6, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(1975, 5, 21)->addMinutes(3)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 2],
                ['id' => 7, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(1975, 5, 21)->addMinutes(4)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 3],
                ['id' => 8, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(1975, 5, 21)->addMinutes(5)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 4],
                ['id' => 9, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(1975, 5, 21)->addMinutes(6)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 5],
                ['id' => 10, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(1975, 5, 21)->addMinutes(7)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 6],
            ],
            'discussion_user' => [
                ['discussion_id' => 3, 'user_id' => 2, 'last_read_post_number' => 6],
                ['discussion_id' => 3, 'user_id' => 4, 'last_read_post_number' => 3],
            ]
        ]);
    }

    #[Test]
    #[DataProvider('deleteLastPostsProvider')]
    public function deleting_last_posts_syncs_discussion_state_for_other_users(array $postIds, int $newLastReadNumber, int $userId)
    {
        // Delete the last post.
        foreach ($postIds as $postId) {
            $this->send(
                $this->request('DELETE', '/api/posts/'.$postId, ['authenticatedAs' => 1])
            );
        }

        // User 2 should now have last_read_post_number set to the new last one
        $this->assertEquals(
            $newLastReadNumber,
            UserState::query()
                ->where('discussion_id', 3)
                ->where('user_id', $userId)
                ->first()
                ->last_read_post_number
        );
    }

    #[Test]
    public function deleting_a_post_that_is_gone_answers_not_found_instead_of_no_content()
    {
        // The row is removed between the endpoint's lookup and the delete.
        $this->extend(
            (new Extend\ApiResource(PostResource::class))->endpoint('delete', function (Endpoint\Delete $endpoint) {
                return $endpoint->before(function () {
                    Post::query()->whereKey(5)->toBase()->delete();
                });
            })
        );

        $response = $this->send($this->request('DELETE', '/api/posts/5', ['authenticatedAs' => 1]));

        $this->assertEquals(404, $response->getStatusCode(), (string) $response->getBody());
        $this->assertFalse(Post::query()->whereKey(5)->exists());
    }

    #[Test]
    public function a_delete_vetoed_by_a_listener_is_not_reported_as_missing()
    {
        // Booting the app also gives the models their event dispatcher.
        $this->app();

        // The row the endpoint deletes is a comment, so the event that the
        // listener can veto is the one for the CommentPost class.
        Post::getEventDispatcher()->listen('eloquent.deleting: '.CommentPost::class, fn () => false);

        $response = $this->send($this->request('DELETE', '/api/posts/5', ['authenticatedAs' => 1]));

        // The row was not removed, but it is still there for the caller to
        // see: this is a refusal, not a missing resource.
        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertTrue($this->database()->table('posts')->where('id', 5)->exists());
    }

    /**
     * The same retry applies to deleting one post: its own transaction is
     * retried, so the events its failed attempt raised must not be raised a
     * second time.
     */
    #[Test]
    public function a_retried_delete_dispatches_the_deleted_event_once()
    {
        $dispatched = 0;
        $attempts = 0;

        $this->extend(
            (new Extend\Event())->listen(Deleted::class, function () use (&$dispatched) {
                $dispatched++;
            }),
            (new Extend\Event())->listen(DeletingPosts::class, function (DeletingPosts $event) use (&$attempts) {
                if (++$attempts === 1) {
                    $event->afterDeletion(function () {
                        throw new PDOException('Deadlock found when trying to get lock; try restarting transaction', 1213);
                    });
                }
            })
        );

        $db = $this->database();
        $id = random_int(1000000000, 2000000000);
        $now = Carbon::now()->toDateTimeString();

        // The retry only happens on the outermost transaction.
        $db->rollBack();

        try {
            $db->table('discussions')->insert([
                'id' => $id, 'title' => 'Retried post deletion', 'slug' => "retried-post-$id", 'user_id' => 1,
                'created_at' => $now, 'comment_count' => 2, 'last_post_number' => 2,
            ]);
            $db->table('posts')->insert([
                ['id' => $id, 'number' => 1, 'discussion_id' => $id, 'user_id' => 1,
                    'created_at' => $now, 'type' => 'comment', 'content' => '<t><p>kept</p></t>'],
                ['id' => $id + 1, 'number' => 2, 'discussion_id' => $id, 'user_id' => 1,
                    'created_at' => $now, 'type' => 'comment', 'content' => '<t><p>deleted</p></t>'],
            ]);
            $db->table('discussions')->where('id', $id)->update(['first_post_id' => $id, 'last_post_id' => $id + 1]);

            $response = $this->send($this->request('DELETE', '/api/posts/'.($id + 1), ['authenticatedAs' => 1]));

            $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
            $this->assertSame(2, $attempts, 'The deletion should have been attempted twice.');
            $this->assertSame(1, $dispatched, 'A retried deletion must dispatch Deleted once.');
            $this->assertFalse($db->table('posts')->where('id', $id + 1)->exists());
        } finally {
            while ($db->transactionLevel() > 0) {
                $db->rollBack();
            }

            $db->table('posts')->where('discussion_id', $id)->delete();
            $db->table('discussions')->where('id', $id)->delete();
        }
    }

    public static function deleteLastPostsProvider(): array
    {
        return [
            // User 2
            [[10], 5, 2],
            [[9, 10], 4, 2],
            [[10, 9, 8], 3, 2],
            [[8, 9, 10], 3, 2],
            [[7, 8, 9, 10], 2, 2],

            // User 4
            [[10], 3, 4],
            [[9, 10], 3, 4],
            [[10, 9, 8], 3, 4],
            [[8, 9, 10], 3, 4],
            [[10, 9, 8, 7], 2, 4],
            [[7, 8, 9, 10], 2, 4],
        ];
    }
}
