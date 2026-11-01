<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\extend;

use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Extend\Mcp;
use Flarum\Mcp\Tests\integration\extend\Fixtures\BrokenTool;
use Flarum\Mcp\Tests\integration\extend\Fixtures\EchoTool;
use Flarum\Mcp\Tests\integration\McpTestCase;
use Flarum\Post\Post;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class ExtenderTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extend(
            (new Mcp())
                ->tool(EchoTool::class)
                ->tool(BrokenTool::class)
                ->removeTool('whoami')
                ->removeTool('whoami')
                ->removeTool('no_such_tool')
                ->extendTool('missing_tool', schema: fn (array $schema) => $schema)
                ->extendTool(
                    'search_discussions',
                    schema: function (array $schema) {
                        $schema['properties']['mine'] = ['type' => 'boolean', 'description' => 'Only my discussions.'];

                        return $schema;
                    },
                    handler: function (array $arguments, array $query, McpContext $context) {
                        if (! empty($arguments['mine'])) {
                            $query['filter']['author'] = $context->actor->username;
                        }

                        return $query;
                    },
                )
                ->describe('discussions', 'Custom description of discussions.')
                ->allowWrites('users', ['delete'])
                ->hideEndpoint('discussions', 'delete')
                ->endpointTool('users', 'avatar.delete', name: 'remove_avatar', title: 'Remove an avatar', description: 'Removes a user avatar.')
                ->ability('test.wave', 'Wave at people')
                ->instructions('Echo is for testing.')
                ->instructions(fn (McpContext $context) => $context->actor->isGuest() ? null : 'You are signed in.')
        );

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            'group_permission' => [['group_id' => Group::MEMBER_ID, 'permission' => 'test.wave']],
            Discussion::class => [
                ['id' => 1, 'title' => 'By admin', 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'By normal', 'user_id' => 2, 'first_post_id' => 2, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>A</p></t>'],
                ['id' => 2, 'discussion_id' => 2, 'number' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>B</p></t>'],
            ],
        ]);
    }

    #[Test]
    public function registrations_are_visible_in_tools_list(): void
    {
        $tools = array_column($this->rpcResult($this->modern('tools/list', [], $this->bearer($this->developerToken(1))))['tools'], null, 'name');

        $this->assertArrayHasKey('echo', $tools);
        $this->assertArrayHasKey('remove_avatar', $tools);
        $this->assertArrayNotHasKey('whoami', $tools);
        $this->assertArrayNotHasKey('broken', $tools);

        $this->assertArrayHasKey('mine', $tools['search_discussions']['inputSchema']['properties']);
        $this->assertSame(['id'], $tools['remove_avatar']['inputSchema']['required']);
        $this->assertFalse($tools['remove_avatar']['annotations']['readOnlyHint']);

        $this->assertContains('users', $tools['delete_resource']['inputSchema']['properties']['type']['enum']);
        $this->assertNotContains('discussions', $tools['delete_resource']['inputSchema']['properties']['type']['enum']);
    }

    #[Test]
    public function a_registered_tool_runs_and_reports_errors(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $result = $this->rpcResult($this->callTool('echo', ['text' => 'hi'], $auth));
        $this->assertSame(['echo' => 'hi', 'by' => 'normal'], $result['structuredContent']);
        $this->assertSame(['echo' => 'hi', 'by' => 'normal'], json_decode($result['content'][0]['text'], true));

        $this->assertSame('Echo refused.', $this->toolOutput($this->callTool('echo', ['text' => 'fail'], $auth), expectError: true)['error']);

        $removed = $this->toolOutput($this->callTool('remove_avatar', ['id' => '2'], $auth));
        $this->assertSame('2', $removed['data']['id']);

        $crash = $this->json($this->callTool('echo', ['text' => 'crash'], $auth));
        $this->assertSame(-32603, $crash['error']['code']);
        $this->assertStringNotContainsString('secret internal detail', (string) json_encode($crash));
    }

    #[Test]
    public function an_extended_tool_maps_its_new_argument(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $all = $this->toolOutput($this->callTool('search_discussions', [], $auth));
        $this->assertEqualsCanonicalizing(['1', '2'], array_column($all['data'], 'id'));

        $mine = $this->toolOutput($this->callTool('search_discussions', ['mine' => true], $auth));
        $this->assertSame(['2'], array_column($mine['data'], 'id'));
    }

    #[Test]
    public function describe_api_reflects_descriptions_and_writes(): void
    {
        $auth = $this->bearer($this->developerToken(1));

        $types = array_column($this->toolOutput($this->callTool('describe_api', [], $auth))['types'], null, 'type');
        $this->assertSame('Custom description of discussions.', $types['discussions']['description']);
        $this->assertContains('delete', $types['users']['operations']);
        $this->assertNotContains('delete', $types['discussions']['operations']);
        $this->assertSame('core', $types['discussions']['extension']);

        $discussions = $this->toolOutput($this->callTool('describe_api', ['type' => 'discussions'], $auth));
        $attributes = array_column($discussions['attributes'], null, 'name');
        $this->assertSame('string', $attributes['title']['schema']['type']);
        $this->assertSame(['create' => true, 'update' => true], $attributes['title']['writable']);
        $this->assertContains('author', $discussions['filters']);
        $this->assertContains('q', $discussions['filters']);
        $this->assertContains('-lastPostedAt', $discussions['sorts']);
        $this->assertContains('latest', $discussions['sorts']);
        $this->assertSame('-lastPostedAt', $discussions['defaultSort']);
        $relationships = array_column($discussions['relationships'], null, 'name');
        $this->assertSame(['users'], $relationships['user']['types']);
    }

    #[Test]
    public function abilities_and_instructions_reach_the_overview(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $overview = json_decode($this->rpcResult($this->modern('resources/read', ['uri' => 'flarum://forum'], $auth, 'flarum://forum'))['contents'][0]['text'], true);

        $this->assertContains('test.wave', array_column($overview['you']['can'], 'ability'));

        // Extenders applied outside an extension are attributed to core.
        $this->assertSame('Echo is for testing. You are signed in.', $overview['core']['instructions']);

        $discover = $this->rpcResult($this->modern('server/discover', [], $auth));
        $this->assertLessThanOrEqual(2000, mb_strlen($discover['instructions']));
        $this->assertStringContainsString('Echo is for testing.', $discover['instructions']);
    }
}
