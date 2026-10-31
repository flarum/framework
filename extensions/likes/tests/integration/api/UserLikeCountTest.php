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
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
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
    public function deleting_a_post_recounts_every_liker_and_leaves_other_likes_alone()
    {
        $this->database()->table('post_likes')->insert([
            ['post_id' => 1, 'user_id' => 2],
            ['post_id' => 2, 'user_id' => 2],
            ['post_id' => 2, 'user_id' => 4],
            ['post_id' => 1, 'user_id' => 3],
        ]);
        $this->database()->table('users')->whereIn('id', [2, 3, 4])->update(['like_count' => 99]);

        $response = $this->send(
            $this->request('DELETE', '/api/posts/2', ['authenticatedAs' => 1])
        );

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(1, $this->likeCountOf(2));
        $this->assertSame(0, $this->likeCountOf(4));
        // Not a liker of the deleted post, so not recounted.
        $this->assertSame(99, $this->likeCountOf(3));
    }

    #[Test]
    public function the_migration_backfills_the_counts_and_rolls_back()
    {
        $this->database()->table('post_likes')->insert([
            ['post_id' => 1, 'user_id' => 2],
            ['post_id' => 2, 'user_id' => 2],
            ['post_id' => 3, 'user_id' => 2],
            ['post_id' => 2, 'user_id' => 3],
        ]);

        $migration = require __DIR__.'/../../../migrations/2026_10_13_000000_add_like_count_to_users_table.php';
        $schema = $this->database()->getSchemaBuilder();

        $migration['down']($schema);
        $this->assertFalse($schema->hasColumn('users', 'like_count'));

        $migration['up']($schema);
        $this->assertTrue($schema->hasColumn('users', 'like_count'));

        // The like of the private post is not counted.
        $this->assertSame(2, $this->likeCountOf(2));
        $this->assertSame(1, $this->likeCountOf(3));
        $this->assertSame(0, $this->likeCountOf(1));
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
