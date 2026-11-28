<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\discussions;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Deleted;
use Flarum\Extend;
use Flarum\Post\Event\DeletingPosts;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PDOException;
use PHPUnit\Framework\Attributes\Test;

class DeletionTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>'],
            ],
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    #[Test]
    public function admin_can_delete()
    {
        $response = $this->send(
            $this->request('DELETE', '/api/discussions/1', [
                'authenticatedAs' => 1,
                'json' => [],
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());

        // Ensure both the discussion and the corresponding post are deleted
        $this->assertNull($this->database()->table('discussions')->find(1), 'Discussion exists in the DB');
        $this->assertNull($this->database()->table('posts')->find(1), 'Post exists in the DB');
    }

    /**
     * A deletion is retried when a like commits against it. Events raised by
     * the failed attempt stay pending until the request dispatches them, so
     * without clearing them the retry dispatches every event twice.
     */
    #[Test]
    public function a_retried_deletion_dispatches_the_deleted_event_once()
    {
        $dispatched = 0;
        $attempts = 0;

        $this->extend(
            (new Extend\Event())->listen(Deleted::class, function () use (&$dispatched) {
                $dispatched++;
            }),
            (new Extend\Event())->listen(DeletingPosts::class, function (DeletingPosts $event) use (&$attempts) {
                if (++$attempts === 1) {
                    // The conflict only surfaces after the posts are gone, the
                    // way a rejected counter update does.
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
                'id' => $id, 'title' => 'Retried deletion', 'slug' => "retried-$id", 'user_id' => 1,
                'created_at' => $now, 'comment_count' => 1, 'last_post_number' => 1,
            ]);
            $db->table('posts')->insert([
                'id' => $id, 'number' => 1, 'discussion_id' => $id, 'user_id' => 1,
                'created_at' => $now, 'type' => 'comment', 'content' => '<t><p>one</p></t>',
            ]);
            $db->table('discussions')->where('id', $id)->update(['first_post_id' => $id, 'last_post_id' => $id]);

            $response = $this->send($this->request('DELETE', "/api/discussions/$id", ['authenticatedAs' => 1]));

            $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
            $this->assertSame(2, $attempts, 'The deletion should have been attempted twice.');
            $this->assertSame(1, $dispatched, 'A retried deletion must dispatch Deleted once.');
            $this->assertFalse($db->table('discussions')->where('id', $id)->exists());
        } finally {
            while ($db->transactionLevel() > 0) {
                $db->rollBack();
            }

            $db->table('posts')->where('discussion_id', $id)->delete();
            $db->table('discussions')->where('id', $id)->delete();
        }
    }
}
