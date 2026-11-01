<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\integrations;

use Flarum\Discussion\Discussion;
use Flarum\Flags\Flag;
use Flarum\Mcp\Tests\integration\McpTestCase;
use Flarum\Post\Post;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class FlagsTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-flags');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => [
                ['id' => 1, 'title' => 'Flag me', 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'last_post_number' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Buy now</p></t>'],
            ],
        ]);
    }

    #[Test]
    public function flag_post_flags_a_post_for_the_moderators(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        // "Other" needs saying what is wrong; refused before anything is sent.
        $vague = $this->toolOutput($this->callTool('flag_post', ['postId' => '1', 'reason' => 'other'], $auth), expectError: true);
        $this->assertStringContainsString('in detail', $vague['error']);

        $flagged = $this->toolOutput($this->callTool('flag_post', ['postId' => '1', 'reason' => 'spam', 'detail' => 'Selling things'], $auth));
        $this->assertTrue($flagged['flagged']);

        $flag = Flag::query()->findOrFail($flagged['flagId']);
        $this->assertSame([1, 2, 'spam', 'Selling things'], [$flag->post_id, $flag->user_id, $flag->reason, $flag->reason_detail]);
    }

    #[Test]
    public function flag_post_with_another_reason_stores_the_detail(): void
    {
        $flagged = $this->toolOutput($this->callTool('flag_post', ['postId' => '1', 'reason' => 'other', 'detail' => 'Not in English'], $this->bearer($this->developerToken(2))));

        $flag = Flag::query()->findOrFail($flagged['flagId']);
        $this->assertNull($flag->reason);
        $this->assertSame('Not in English', $flag->reason_detail);
    }

    #[Test]
    public function dismiss_flags_is_for_those_who_see_flags(): void
    {
        $this->prepareDatabase([
            'flags' => [
                ['id' => 1, 'post_id' => 1, 'user_id' => 2, 'type' => 'user', 'reason' => 'spam', 'created_at' => '2026-01-01 00:00:00'],
            ],
        ]);

        $this->assertNotContains('dismiss_flags', $this->toolNames($this->modern('tools/list', [], $this->bearer($this->developerToken(2)))));

        $admin = $this->bearer($this->developerToken(1));
        $this->assertContains('dismiss_flags', $this->toolNames($this->modern('tools/list', [], $admin)));

        $this->assertSame(['dismissed' => true, 'postId' => '1'], $this->toolOutput($this->callTool('dismiss_flags', ['postId' => '1'], $admin)));
        $this->assertSame(0, Flag::query()->count());
        $this->assertTrue(Post::query()->whereKey(1)->exists());
    }
}
