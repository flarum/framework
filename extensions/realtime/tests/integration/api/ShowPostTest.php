<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Realtime asks for a post's discussion's tags by default, which only exist
 * while Tags is enabled.
 */
class ShowPostTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => 'Hello', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Hello</p></t>'],
            ],
        ]);
    }

    #[Test]
    public function a_post_can_be_shown_without_tags(): void
    {
        $this->extension('flarum-realtime');

        $response = $this->send($this->request('GET', '/api/posts/1', ['authenticatedAs' => 1]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function with_tags_its_discussion_comes_with_them(): void
    {
        $this->extension('flarum-tags', 'flarum-realtime');

        $response = $this->send($this->request('GET', '/api/posts/1', ['authenticatedAs' => 1]));
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('tags', collect($body['included'])->firstWhere('type', 'discussions')['relationships']);
    }
}
