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
 * Listing posts includes their likers by default. Serializing each liker reads
 * their groups, so they must be loaded for the whole page, not per liker: the
 * integration harness fails a request whose queries grow with the likers.
 */
class ListPostsLikesTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-likes');

        $users = [];
        $posts = [];
        $likes = [];

        foreach (range(2, 11) as $id) {
            $users[] = ['id' => $id, 'username' => "liker$id", 'email' => "liker$id@machine.local", 'is_email_confirmed' => 1];
        }

        foreach (range(1, 10) as $id) {
            $posts[] = ['id' => $id, 'number' => $id, 'discussion_id' => 1, 'user_id' => 1, 'created_at' => Carbon::now()->subMinutes($id), 'type' => 'comment', 'content' => "<t><p>Post $id</p></t>"];
            $likes[] = ['post_id' => $id, 'user_id' => $id + 1];
        }

        $this->prepareDatabase([
            User::class => $users,
            Discussion::class => [
                ['id' => 1, 'title' => 'Liked', 'user_id' => 1, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'first_post_id' => 1, 'comment_count' => 10, 'last_post_number' => 10],
            ],
            Post::class => $posts,
            'post_likes' => $likes,
        ]);
    }

    #[Test]
    public function likers_are_loaded_for_the_whole_page(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 1])->withQueryParams(['filter' => ['discussion' => '1']])
        );

        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertCount(10, collect($body['included'])->where('type', 'users')->where('id', '!=', '1'));
    }
}
