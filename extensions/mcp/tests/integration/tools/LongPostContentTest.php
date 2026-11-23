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
use Flarum\Mcp\Api\ApiResult;
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
                ['id' => 2, 'discussion_id' => 1, 'number' => 2, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>banana</p></t>', 'created_at' => Carbon::now()->subDay()],
                ['id' => 3, 'discussion_id' => 1, 'number' => 3, 'user_id' => 2, 'type' => 'discussionRenamed', 'content' => json_encode(['Old', 'Long']), 'created_at' => Carbon::now()->subHour()],
            ],
        ]);
    }

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

    #[Test]
    public function an_editors_longest_post_comes_back_whole_within_the_payload_limit(): void
    {
        $source = str_repeat('word ', 12600);
        $this->prepareDatabase([
            Post::class => [['id' => 4, 'discussion_id' => 1, 'number' => 4, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>'.$source.'</p></t>', 'created_at' => Carbon::now()]],
        ]);

        $response = $this->callTool('get_resource', ['type' => 'posts', 'id' => '4'], $this->bearer($this->developerToken(2)));
        $text = $this->rpcResult($response)['content'][0]['text'];
        $post = json_decode($text, true);

        $this->assertLessThanOrEqual(ApiResult::MAX_PAYLOAD_LENGTH, mb_strlen($text));
        $this->assertSame(rtrim($source), rtrim($post['data']['content']));
        $this->assertArrayNotHasKey('truncated', $post['meta'] ?? []);
    }

    #[Test]
    public function edits_apply_in_order_each_on_the_result_of_the_last(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $this->toolOutput($this->callTool('edit_post', [
            'postId' => '1',
            'edits' => [
                ['find' => 'THE ENDING', 'replace' => 'A MIDDLE'],
                ['find' => 'A MIDDLE', 'replace' => 'A MIDDLE AND AN END'],
            ],
        ], $auth));

        $post = $this->toolOutput($this->callTool('get_resource', ['type' => 'posts', 'id' => '1'], $auth));
        $this->assertSame(str_repeat('a', 4990).'A MIDDLE AND AN END', $post['data']['content']);
    }

    #[Test]
    public function text_that_matches_twice_overlapping_is_ambiguous(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $error = $this->toolOutput($this->callTool('edit_post', [
            'postId' => '2',
            'edits' => [['find' => 'ana', 'replace' => 'ANA']],
        ], $auth), expectError: true);

        $this->assertStringContainsString('more than once', $error['error']);

        $post = $this->toolOutput($this->callTool('get_resource', ['type' => 'posts', 'id' => '2'], $auth));
        $this->assertSame('banana', $post['data']['content']);
    }

    #[Test]
    public function edits_may_not_leave_the_post_empty(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $error = $this->toolOutput($this->callTool('edit_post', [
            'postId' => '2',
            'edits' => [['find' => 'banana', 'replace' => ' ']],
        ], $auth), expectError: true);

        $this->assertStringContainsString('empty', $error['error']);

        $post = $this->toolOutput($this->callTool('get_resource', ['type' => 'posts', 'id' => '2'], $auth));
        $this->assertSame('banana', $post['data']['content']);
    }

    #[Test]
    public function content_and_edits_together_are_refused(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $error = $this->toolOutput($this->callTool('edit_post', [
            'postId' => '2',
            'content' => 'Whole new post',
            'edits' => [['find' => 'banana', 'replace' => 'apple']],
        ], $auth), expectError: true);

        $this->assertSame('Give either "content" or "edits", not both.', $error['error']);

        $post = $this->toolOutput($this->callTool('get_resource', ['type' => 'posts', 'id' => '2'], $auth));
        $this->assertSame('banana', $post['data']['content']);
    }

    #[Test]
    public function an_event_post_cannot_be_edited_in_part(): void
    {
        $error = $this->toolOutput($this->callTool('edit_post', [
            'postId' => '3',
            'edits' => [['find' => 'Old', 'replace' => 'New']],
        ], $this->bearer($this->developerToken(1))), expectError: true);

        $this->assertSame('Only comments can be edited.', $error['error']);
    }

    #[Test]
    public function a_long_find_is_shortened_in_the_error(): void
    {
        $find = str_repeat('z', 5000);

        $error = $this->toolOutput($this->callTool('edit_post', [
            'postId' => '2',
            'edits' => [['find' => $find, 'replace' => 'x']],
        ], $this->bearer($this->developerToken(2))), expectError: true);

        $this->assertStringNotContainsString($find, $error['error']);
        $this->assertLessThan(500, mb_strlen($error['error']));
    }

    #[Test]
    public function edit_post_does_not_claim_to_be_idempotent(): void
    {
        $tools = array_column($this->rpcResult($this->modern('tools/list', [], $this->bearer($this->developerToken(2))))['tools'], null, 'name');

        $this->assertFalse($tools['edit_post']['annotations']['idempotentHint']);
    }

    #[Test]
    public function more_than_fifty_edits_are_refused(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $body = $this->mcpJson($this->callTool('edit_post', [
            'postId' => '2',
            'edits' => array_fill(0, 51, ['find' => 'banana', 'replace' => 'apple']),
        ], $auth));

        $this->assertSame(-32602, $body['error']['code'] ?? null, (string) json_encode($body));

        $post = $this->toolOutput($this->callTool('get_resource', ['type' => 'posts', 'id' => '2'], $auth));
        $this->assertSame('banana', $post['data']['content']);
    }
}
