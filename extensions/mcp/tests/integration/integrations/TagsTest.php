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
use Flarum\Tags\Tag;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class TagsTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Tag::class => [
                ['id' => 1, 'name' => 'General', 'slug' => 'general', 'description' => 'Anything goes', 'position' => 0, 'is_primary' => true, 'discussion_count' => 1],
                ['id' => 2, 'name' => 'Support', 'slug' => 'support', 'position' => 1, 'is_primary' => true],
                ['id' => 3, 'name' => 'Billing', 'slug' => 'billing', 'position' => 0, 'is_primary' => true, 'parent_id' => 2],
                ['id' => 4, 'name' => 'Ideas', 'slug' => 'ideas'],
                ['id' => 5, 'name' => 'Staff', 'slug' => 'staff', 'position' => 2, 'is_primary' => true, 'is_restricted' => true],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'In general', 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'last_post_number' => 1],
                ['id' => 2, 'title' => 'Needs support', 'user_id' => 1, 'first_post_id' => 2, 'comment_count' => 1, 'last_post_number' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Hello</p></t>'],
                ['id' => 2, 'discussion_id' => 2, 'number' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Help</p></t>'],
            ],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
                ['discussion_id' => 2, 'tag_id' => 2],
            ],
        ]);
    }

    #[Test]
    public function list_tags_returns_the_tree_the_actor_can_see(): void
    {
        $tags = $this->toolOutput($this->callTool('list_tags', [], $this->bearer($this->developerToken(2))));

        $this->assertSame(['general', 'support'], array_column($tags['primary'], 'slug'));
        $this->assertSame('Anything goes', $tags['primary'][0]['description']);
        $this->assertSame('http://localhost/t/general', $tags['primary'][0]['url']);
        $this->assertTrue($tags['primary'][0]['canStartDiscussion']);
        $this->assertSame(['billing'], array_column($tags['primary'][1]['children'], 'slug'));
        $this->assertSame(['ideas'], array_column($tags['secondary'], 'slug'));
    }

    #[Test]
    public function search_discussions_filters_by_tag_slug(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $schema = array_column($this->rpcResult($this->modern('tools/list', [], $auth))['tools'], 'inputSchema', 'name')['search_discussions'];
        $this->assertArrayHasKey('tag', $schema['properties']);

        $found = $this->toolOutput($this->callTool('search_discussions', ['tag' => 'support'], $auth));

        $this->assertSame(['2'], array_column($found['data'], 'id'));
    }

    #[Test]
    public function start_discussion_files_the_discussion_under_tags(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $created = $this->toolOutput($this->callTool('start_discussion', [
            'title' => 'Tagged over MCP',
            'content' => 'Hello',
            'tags' => ['general'],
        ], $auth));

        $this->assertSame([1], Discussion::query()->findOrFail($created['id'])->tags()->pluck('tags.id')->all());

        $unknown = $this->toolOutput($this->callTool('start_discussion', [
            'title' => 'Badly tagged',
            'content' => 'Hello',
            'tags' => ['nope'],
        ], $auth), expectError: true);

        $this->assertStringContainsString('Unknown tag(s): nope', $unknown['error']);
    }
}
