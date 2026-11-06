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

/**
 * Likes are stored to the second, so several likers can share a timestamp.
 * Their order must not depend on what the database happens to return.
 */
class ListPostsLikersOrderTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-likes');

        $users = [];
        $likes = [];
        $time = Carbon::parse('2024-01-01 12:00:00');

        // Inserted out of id order, all with the same created_at.
        foreach ([9, 4, 7, 2, 11, 5, 3, 8, 6, 10] as $id) {
            $users[] = ['id' => $id, 'username' => "liker$id", 'email' => "liker$id@machine.local", 'is_email_confirmed' => 1];
            $likes[] = ['post_id' => 1, 'user_id' => $id, 'created_at' => $time];
        }

        $this->prepareDatabase([
            User::class => $users,
            Discussion::class => [
                ['id' => 1, 'title' => 'Liked', 'user_id' => 1, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'first_post_id' => 1, 'comment_count' => 1, 'last_post_number' => 1],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'user_id' => 1, 'created_at' => Carbon::now(), 'type' => 'comment', 'content' => '<t><p>Post</p></t>'],
            ],
            'post_likes' => $likes,
        ]);
    }

    /**
     * @return list<int>
     */
    private function likerIds(int $actorId): array
    {
        $response = $this->send(
            $this->request('GET', '/api/posts/1', ['authenticatedAs' => $actorId])->withQueryParams(['include' => 'likes'])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $body = json_decode((string) $response->getBody(), true);

        return array_map(
            fn (array $liker) => (int) $liker['id'],
            $body['data']['relationships']['likes']['data']
        );
    }

    #[Test]
    public function likers_who_liked_at_the_same_time_are_ordered_by_user_id(): void
    {
        $first = $this->likerIds(1);

        $this->assertSame([2, 3, 4, 5], $first);
        $this->assertSame($first, $this->likerIds(1));
    }

    #[Test]
    public function the_actor_is_still_listed_first(): void
    {
        $this->assertSame([7, 2, 3, 4], $this->likerIds(7));
    }
}
