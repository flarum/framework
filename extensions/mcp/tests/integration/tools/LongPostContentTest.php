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
use Flarum\Mcp\Testing\McpTestCase;
use Flarum\Post\Post;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Posts longer than the tool output's per-string limit (4,000 characters).
 */
class LongPostContentTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => [
                ['id' => 1, 'title' => 'Long', 'slug' => 'long', 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'last_post_number' => 1, 'created_at' => Carbon::now()->subDay()],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>'.$this->longText().'</p></t>', 'created_at' => Carbon::now()->subDay()],
            ],
        ]);
    }

    /**
     * 5,000 characters, the last of them a distinct ending.
     */
    protected function longText(): string
    {
        return str_repeat('a', 4990).'THE ENDING';
    }

    #[Test]
    public function get_resource_returns_a_long_post_in_full(): void
    {
        $post = $this->toolOutput($this->callTool('get_resource', [
            'type' => 'posts', 'id' => '1',
        ], $this->bearer($this->developerToken(2))));

        $this->assertSame($this->longText(), $post['data']['content']);
    }

    #[Test]
    public function edit_post_replaces_only_the_text_it_is_given(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $this->toolOutput($this->callTool('edit_post', [
            'postId' => '1',
            'edits' => [['find' => 'THE ENDING', 'replace' => 'A NEW ENDING']],
        ], $auth));

        $post = $this->toolOutput($this->callTool('get_resource', ['type' => 'posts', 'id' => '1'], $auth));

        $this->assertSame(str_repeat('a', 4990).'A NEW ENDING', $post['data']['content']);
    }

    #[Test]
    public function an_edit_whose_text_is_not_in_the_post_changes_nothing(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $error = $this->toolOutput($this->callTool('edit_post', [
            'postId' => '1',
            'edits' => [
                ['find' => 'THE ENDING', 'replace' => 'A NEW ENDING'],
                ['find' => 'NOT THERE', 'replace' => 'anything'],
            ],
        ], $auth), expectError: true);

        $this->assertStringContainsString('NOT THERE', $error['error']);

        $post = $this->toolOutput($this->callTool('get_resource', ['type' => 'posts', 'id' => '1'], $auth));
        $this->assertSame($this->longText(), $post['data']['content']);
    }

    #[Test]
    public function an_edit_whose_text_appears_more_than_once_changes_nothing(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $error = $this->toolOutput($this->callTool('edit_post', [
            'postId' => '1',
            'edits' => [['find' => 'aaaa', 'replace' => 'b']],
        ], $auth), expectError: true);

        $this->assertStringContainsString('aaaa', $error['error']);

        $post = $this->toolOutput($this->callTool('get_resource', ['type' => 'posts', 'id' => '1'], $auth));
        $this->assertSame($this->longText(), $post['data']['content']);
    }

    #[Test]
    public function edit_post_needs_content_or_edits(): void
    {
        $error = $this->toolOutput($this->callTool('edit_post', ['postId' => '1'], $this->bearer($this->developerToken(2))), expectError: true);

        $this->assertSame('Give "content" or "edits".', $error['error']);
    }

    #[Test]
    public function edits_to_someone_elses_post_are_a_permission_error(): void
    {
        $this->prepareDatabase([
            User::class => [['id' => 3, 'username' => 'other', 'email' => 'other@machine.local', 'is_email_confirmed' => 1]],
        ]);

        $error = $this->toolOutput($this->callTool('edit_post', [
            'postId' => '1',
            'edits' => [['find' => 'THE ENDING', 'replace' => 'Hijacked']],
        ], $this->bearer($this->developerToken(3))), expectError: true);

        $this->assertSame('You do not have permission to do that.', $error['error']);
    }
}
