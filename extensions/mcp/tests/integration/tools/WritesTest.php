<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\tools;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Mcp\Tests\integration\McpTestCase;
use Flarum\Post\Post;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class WritesTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => [
                ['id' => 1, 'title' => 'Existing', 'slug' => 'existing', 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'last_post_number' => 1, 'created_at' => Carbon::now()->subDay()],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>First</p></t>', 'created_at' => Carbon::now()->subDay()],
            ],
        ]);
    }

    #[Test]
    public function start_discussion_creates_content_as_the_actor(): void
    {
        $created = $this->toolOutput($this->callTool('start_discussion', [
            'title' => 'Started over MCP',
            'content' => 'Hello **world**',
        ], $this->bearer($this->developerToken(2))));

        $this->assertSame('Started over MCP', $created['title']);
        $this->assertStringStartsWith('http://localhost/d/'.$created['id'], $created['url']);

        $discussion = Discussion::query()->findOrFail($created['id']);
        $this->assertSame(2, $discussion->user_id);
        $this->assertSame(2, $discussion->firstPost->user_id);
    }

    #[Test]
    public function validation_errors_are_tool_errors(): void
    {
        $error = $this->toolOutput($this->callTool('start_discussion', [
            'title' => str_repeat('Too long ', 30),
            'content' => 'Hello',
        ], $this->bearer($this->developerToken(2))), expectError: true);

        $this->assertStringContainsString('`title`:', $error['error']);
        $this->assertSame(1, $this->database()->table('discussions')->count());
    }

    #[Test]
    public function reply_to_discussion_posts_as_the_actor(): void
    {
        $reply = $this->toolOutput($this->callTool('reply_to_discussion', [
            'discussionId' => '1',
            'content' => 'A reply',
        ], $this->bearer($this->developerToken(2))));

        $this->assertSame(2, $reply['number']);
        $this->assertSame('http://localhost/d/1-existing/2', $reply['url']);
        $this->assertSame(2, (int) $this->database()->table('posts')->where('id', $reply['id'])->value('user_id'));
    }

    #[Test]
    public function a_second_reply_inside_the_flood_window_is_throttled(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $this->toolOutput($this->callTool('reply_to_discussion', ['discussionId' => '1', 'content' => 'One'], $auth));

        $error = $this->toolOutput($this->callTool('reply_to_discussion', ['discussionId' => '1', 'content' => 'Two'], $auth), expectError: true);

        $this->assertSame('You are doing that too often; wait before trying again.', $error['error']);
        $this->assertSame(2, $this->database()->table('posts')->count());
    }

    #[Test]
    public function replying_to_an_invisible_discussion_does_not_reveal_it(): void
    {
        $error = $this->toolOutput($this->callTool('reply_to_discussion', ['discussionId' => '999', 'content' => 'Hello'], $this->bearer($this->developerToken(2))), expectError: true);

        $this->assertSame('Not found.', $error['error']);
    }

    #[Test]
    public function editing_someone_elses_post_is_a_permission_error(): void
    {
        $error = $this->toolOutput($this->callTool('edit_post', ['postId' => '1', 'content' => 'Hijacked'], $this->bearer($this->developerToken(2))), expectError: true);

        $this->assertSame('You do not have permission to do that.', $error['error']);
        $this->assertSame('<t><p>First</p></t>', $this->database()->table('posts')->where('id', 1)->value('content'));
    }

    #[Test]
    public function generic_update_works_on_an_allow_listed_type(): void
    {
        $updated = $this->toolOutput($this->callTool('update_resource', [
            'type' => 'discussions',
            'id' => '1',
            'attributes' => ['title' => 'Renamed by admin'],
        ], $this->bearer($this->developerToken(1))));

        $this->assertSame('Renamed by admin', $updated['data']['title']);
    }

    #[Test]
    public function a_write_on_a_type_that_is_not_allow_listed_is_refused(): void
    {
        $auth = $this->bearer($this->developerToken(1));

        $tools = array_column($this->rpcResult($this->modern('tools/list', [], $auth))['tools'], null, 'name');
        $this->assertNotContains('users', $tools['create_resource']['inputSchema']['properties']['type']['enum']);
        $this->assertNotContains('users', $tools['delete_resource']['inputSchema']['properties']['type']['enum']);
        $this->assertContains('users', $tools['update_resource']['inputSchema']['properties']['type']['enum']);

        $response = $this->json($this->callTool('delete_resource', ['type' => 'users', 'id' => '2'], $auth));

        $this->assertSame(-32602, $response['error']['code']);
        $this->assertTrue($this->database()->table('users')->where('id', 2)->exists());
    }
}
