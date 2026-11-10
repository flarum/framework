<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes\Tests\integration\api\discussions;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class ListDiscussionsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-likes');

        $this->prepareDatabase([
            Discussion::class => [
                [
                    'id' => 100,
                    'title' => __CLASS__,
                    'created_at' => Carbon::now(),
                    'last_posted_at' => Carbon::now(),
                    'user_id' => 1,
                    'first_post_id' => 101,
                    'last_post_id' => 102,
                    'comment_count' => 2,
                ],
            ],
            Post::class => [
                ['id' => 101, 'discussion_id' => 100, 'number' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>first</p></t>'],
                ['id' => 102, 'discussion_id' => 100, 'number' => 2, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>last</p></t>'],
            ],
            User::class => [
                $this->normalUser(),
                ['id' => 102, 'username' => 'liker', 'email' => 'liker@machine.local', 'is_email_confirmed' => 1],
            ],
            'post_likes' => [
                ['user_id' => 102, 'post_id' => 101, 'created_at' => Carbon::now()],
                ['user_id' => 102, 'post_id' => 102, 'created_at' => Carbon::now()],
            ],
        ]);
    }

    private function listDiscussions(array $queryParams = [])
    {
        return $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 1])
                ->withQueryParams($queryParams)
        );
    }

    private function includedTypes(string $body): array
    {
        return array_column(json_decode($body, true)['included'] ?? [], 'type');
    }

    #[Test]
    public function index_does_not_default_include_posts_or_their_likes()
    {
        // The discussion list UI never renders firstPost/lastPost likes, so the
        // Index endpoint should not pay to serialize those posts (and their
        // likers) by default. See flarum/framework#4788.
        $response = $this->listDiscussions();

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertNotContains('posts', $this->includedTypes((string) $response->getBody()));
    }

    #[Test]
    public function index_still_allows_explicitly_including_post_likes()
    {
        // Dropping the default include must not remove the ability to request it.
        $response = $this->listDiscussions([
            'include' => 'firstPost,firstPost.likes,lastPost,lastPost.likes',
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);

        $this->assertContains('posts', $this->includedTypes($body));

        // The requested firstPost should carry its likes relationship.
        $firstPost = collect($decoded['included'])->firstWhere(fn ($r) => $r['type'] === 'posts' && $r['id'] === '101');
        $this->assertNotNull($firstPost);
        $this->assertArrayHasKey('likes', $firstPost['relationships']);
    }
}
