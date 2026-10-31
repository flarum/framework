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

class SubscriptionsTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-subscriptions');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => [
                ['id' => 1, 'title' => 'Worth following', 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'last_post_number' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Follow me</p></t>'],
            ],
        ]);
    }

    #[Test]
    public function follow_discussion_sets_the_actors_subscription(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $followed = $this->toolOutput($this->callTool('follow_discussion', ['discussionId' => '1'], $auth));
        $this->assertSame(['discussionId' => '1', 'title' => 'Worth following', 'state' => 'follow'], $followed);
        $this->assertSame('follow', $this->subscription());

        $this->assertSame('ignore', $this->toolOutput($this->callTool('follow_discussion', ['discussionId' => '1', 'state' => 'ignore'], $auth))['state']);
        $this->assertSame('ignore', $this->subscription());

        $this->assertSame('none', $this->toolOutput($this->callTool('follow_discussion', ['discussionId' => '1', 'state' => 'none'], $auth))['state']);
        $this->assertNull($this->subscription());
    }

    protected function subscription(): ?string
    {
        return $this->database()->table('discussion_user')->where(['discussion_id' => 1, 'user_id' => 2])->value('subscription');
    }
}
