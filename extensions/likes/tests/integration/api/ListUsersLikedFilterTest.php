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
 * `filter[liked]=<postId>` lists the users who liked the post, counting only posts the actor can see.
 */
class ListUsersLikedFilterTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-likes');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'liker', 'email' => 'liker@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'otherliker', 'email' => 'otherliker@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'private', 'user_id' => 1, 'comment_count' => 1, 'is_private' => 1, 'first_post_id' => 1, 'last_post_id' => 1],
                ['id' => 2, 'title' => 'public', 'user_id' => 1, 'comment_count' => 1, 'first_post_id' => 2, 'last_post_id' => 2],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t>private</t>', 'number' => 1, 'is_private' => 1],
                ['id' => 2, 'discussion_id' => 2, 'user_id' => 1, 'type' => 'comment', 'content' => '<t>public</t>', 'number' => 1],
            ],
            'post_likes' => [
                ['post_id' => 1, 'user_id' => 3, 'created_at' => Carbon::now()],
                ['post_id' => 2, 'user_id' => 4, 'created_at' => Carbon::now()],
            ],
        ]);
    }

    /**
     * @return list<int>
     */
    private function ids(int $actor, array $filter): array
    {
        $response = $this->send(
            $this->request('GET', '/api/users', ['authenticatedAs' => $actor])->withQueryParams(['filter' => $filter, 'page' => ['limit' => 50]])
        );

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());

        $ids = array_map('intval', array_column(json_decode((string) $response->getBody(), true)['data'], 'id'));
        sort($ids);

        return $ids;
    }

    #[Test]
    public function a_post_the_actor_cannot_see_behaves_like_a_nonexistent_one()
    {
        $this->assertSame([], $this->ids(2, ['liked' => '1']));
        $this->assertSame($this->ids(2, ['-liked' => '999']), $this->ids(2, ['-liked' => '1']));
        $this->assertContains(3, $this->ids(2, ['-liked' => '1']));
    }

    #[Test]
    public function the_likers_of_a_visible_post_are_listed()
    {
        $this->assertSame([4], $this->ids(2, ['liked' => '2']));
        $this->assertNotContains(4, $this->ids(2, ['-liked' => '2']));
    }
}
