<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\integrations;

use Flarum\Discussion\Discussion;
use Flarum\Mcp\Tests\integration\McpTestCase;
use Flarum\Post\Post;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class LikesTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-likes');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => [
                ['id' => 1, 'title' => 'Likeable', 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'last_post_number' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Like me</p></t>'],
            ],
        ]);
    }

    #[Test]
    public function like_and_unlike_a_post_as_the_actor(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $liked = $this->toolOutput($this->callTool('like_post', ['postId' => '1'], $auth));
        $this->assertSame(['postId' => '1', 'liked' => true, 'likesCount' => 1], $liked);
        $this->assertSame([2], $this->database()->table('post_likes')->where('post_id', 1)->pluck('user_id')->map(fn ($id) => (int) $id)->all());

        $unliked = $this->toolOutput($this->callTool('unlike_post', ['postId' => 1], $auth));
        $this->assertFalse($unliked['liked']);
        $this->assertSame(0, $this->database()->table('post_likes')->where('post_id', 1)->count());
    }
}
