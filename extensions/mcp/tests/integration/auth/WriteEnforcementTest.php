<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\auth;

use Flarum\Group\Group;
use Flarum\Mcp\Extend\Mcp;
use Flarum\Mcp\Tests\integration\extend\Fixtures\MislabelledTool;
use Flarum\Mcp\Tests\integration\extend\Fixtures\UndeclaredTool;
use Flarum\Mcp\Tests\integration\IssuesOAuthTokens;
use Flarum\Mcp\Tests\integration\McpTestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Writes need mcp:write whatever a tool declares about itself: ApiClient
 * enforces it, and endpoint tools take their read-only flag from the HTTP method.
 */
class WriteEnforcementTest extends McpTestCase
{
    use IssuesOAuthTokens;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extend(
            (new Mcp())
                ->tool(MislabelledTool::class)
                ->tool(UndeclaredTool::class)
                ->endpointTool('users', 'avatar.delete', name: 'remove_avatar', title: 'Remove an avatar', description: 'Removes a user avatar.')
                ->endpointTool('discussions', 'show', name: 'peek_discussion', title: 'Peek', description: 'Shows a discussion.')
        );

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
        ]);
    }

    #[Test]
    public function a_mislabelled_tool_cannot_write_with_a_read_only_token_in_the_modern_era(): void
    {
        $this->setUpOAuth();
        $auth = $this->bearer($this->issueOAuthToken('mcp:read'));

        $tools = array_column($this->rpcResult($this->modern('tools/list', [], $auth))['tools'], null, 'name');
        $this->assertTrue($tools['harmless_lookup']['annotations']['readOnlyHint']);

        $error = $this->toolOutput($this->callTool('harmless_lookup', [], $auth), expectError: true);

        $this->assertSame('This requires write access. Reconnect and grant permission to make changes.', $error['error']);
        $this->assertSame(0, $this->database()->table('discussions')->count());
    }

    #[Test]
    public function a_mislabelled_tool_cannot_write_with_a_read_only_token_in_the_handshake_era(): void
    {
        $this->setUpOAuth();
        $auth = $this->bearer($this->issueOAuthToken('mcp:read'));

        $session = $this->initializeSession($auth);
        $result = $this->rpcResult($this->inSession($session, 'tools/call', ['name' => 'harmless_lookup', 'arguments' => new \stdClass()], $auth));

        $this->assertTrue($result['isError']);
        $this->assertSame('This requires write access. Reconnect and grant permission to make changes.', $result['content'][0]['text']);
        $this->assertSame(0, $this->database()->table('discussions')->count());
    }

    #[Test]
    public function a_mislabelled_tool_cannot_write_as_a_guest(): void
    {
        $this->prepareDatabase([
            'group_permission' => [['group_id' => Group::GUEST_ID, 'permission' => 'mcp.use']],
        ]);

        $error = $this->toolOutput($this->callTool('harmless_lookup'), expectError: true);

        $this->assertSame('This requires signing in. Connect with your forum account and try again.', $error['error']);
        $this->assertSame(0, $this->database()->table('discussions')->count());
    }

    #[Test]
    public function a_mislabelled_tool_writes_with_the_write_scope(): void
    {
        $output = $this->toolOutput($this->callTool('harmless_lookup', [], $this->bearer($this->developerToken(2))));

        $this->assertSame('Sneaky', $output['data']['title']);
    }

    #[Test]
    public function an_endpoint_tool_takes_read_only_from_the_http_method(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $tools = array_column($this->rpcResult($this->modern('tools/list', [], $auth))['tools'], null, 'name');

        $this->assertFalse($tools['remove_avatar']['annotations']['readOnlyHint']);
        $this->assertTrue($tools['peek_discussion']['annotations']['readOnlyHint']);
    }

    #[Test]
    public function a_write_endpoint_tool_is_refused_for_a_read_only_token_in_both_eras(): void
    {
        $this->setUpOAuth();
        $auth = $this->bearer($this->issueOAuthToken('mcp:read'));

        $modern = $this->callTool('remove_avatar', ['id' => '2'], $auth);
        $this->assertSame(403, $modern->getStatusCode());
        $this->assertStringContainsString('error="insufficient_scope"', $modern->getHeaderLine('WWW-Authenticate'));

        $session = $this->initializeSession($auth);
        $handshake = $this->inSession($session, 'tools/call', ['name' => 'remove_avatar', 'arguments' => ['id' => '2']], $auth);
        $this->assertSame(403, $handshake->getStatusCode());
        $this->assertStringContainsString('scope="mcp:read mcp:write"', $handshake->getHeaderLine('WWW-Authenticate'));
    }

    #[Test]
    public function a_tool_that_does_not_declare_read_only_is_a_write(): void
    {
        $this->setUpOAuth();
        $auth = $this->bearer($this->issueOAuthToken('mcp:read'));

        $tools = array_column($this->rpcResult($this->modern('tools/list', [], $auth))['tools'], null, 'name');
        $this->assertFalse($tools['undeclared']['annotations']['readOnlyHint']);

        $this->assertSame(403, $this->callTool('undeclared', [], $auth)->getStatusCode());
    }
}
