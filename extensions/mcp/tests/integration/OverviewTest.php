<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration;

use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class OverviewTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The bundled integrations come from flarum/mcp's own extend.php, through
        // Extend\Conditional, exactly as in production.
        $this->extension('flarum-tags', 'flarum-lock');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
        ]);
    }

    #[Test]
    public function overview_lists_enabled_extensions_with_their_tools_and_types(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $read = $this->rpcResult($this->modern('resources/read', ['uri' => 'flarum://forum'], $auth, 'flarum://forum'));
        $this->assertSame('application/json', $read['contents'][0]['mimeType']);
        $overview = json_decode($read['contents'][0]['text'], true);

        $this->assertSame('http://localhost', $overview['forum']['url']);
        $this->assertFalse($overview['you']['guest']);
        $this->assertSame('normal', $overview['you']['username']);
        $this->assertContains('startDiscussion', array_column($overview['you']['can'], 'ability'));

        $extensions = array_column($overview['extensions'], null, 'id');
        $this->assertArrayHasKey('flarum-tags', $extensions);
        $this->assertArrayHasKey('flarum-mcp', $extensions);

        $this->assertSame(['list_tags'], $extensions['flarum-tags']['tools']);
        $this->assertContains('tags', $extensions['flarum-tags']['types']);
        $this->assertStringContainsString('{"isLocked": true}', (string) $extensions['flarum-lock']['instructions']);

        $this->assertContains('search_discussions', $extensions['flarum-mcp']['tools']);
        $this->assertNotContains('list_tags', $extensions['flarum-mcp']['tools']);
        $this->assertNull($extensions['flarum-mcp']['instructions']);
        $this->assertContains('flarum://forum', $extensions['flarum-mcp']['resources']);
        $this->assertContains('summarize_discussion', $extensions['flarum-mcp']['prompts']);

        $this->assertContains('discussions', $overview['core']['types']);
        $this->assertNotContains('tags', $overview['core']['types']);
        $this->assertNotEmpty($overview['guidance']);
    }

    #[Test]
    public function instructions_summarise_the_forum_for_the_actor(): void
    {
        $instructions = $this->rpcResult($this->modern('server/discover', [], $this->bearer($this->developerToken(2))))['instructions'];

        $this->assertStringContainsString('You are connected as', $instructions);
        $this->assertStringContainsString('(@normal)', $instructions);
        $this->assertStringContainsString('Enabled extensions:', $instructions);
        $this->assertStringContainsString('- Lock: ', $instructions);
        $this->assertLessThanOrEqual(2000, mb_strlen($instructions));
    }

    #[Test]
    public function whoami_and_user_tools_describe_people(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $user = $this->toolOutput($this->callTool('get_user', ['username' => 'normal'], $auth));
        $this->assertSame('2', $user['id']);
        $this->assertSame('http://localhost/u/normal', $user['url']);
        $this->assertArrayNotHasKey('canEdit', $user);

        $template = $this->rpcResult($this->modern('resources/read', ['uri' => 'flarum://users/admin'], $auth, 'flarum://users/admin'));
        $this->assertSame('admin', json_decode($template['contents'][0]['text'], true)['username']);

        $missing = $this->toolOutput($this->callTool('get_user', ['username' => 'nobody'], $auth), expectError: true);
        $this->assertSame('Not found.', $missing['error']);
    }
}
