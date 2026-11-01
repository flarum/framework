<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\protocol;

use Flarum\Mcp\Tests\integration\McpTestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class TransportTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
        ]);
    }

    #[Test]
    public function handshake_era_session_persists_across_requests(): void
    {
        $token = $this->developerToken(2);

        $initialize = $this->send($this->mcpRequest([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => self::HANDSHAKE,
                'capabilities' => new \stdClass(),
                'clientInfo' => ['name' => 'flarum-tests', 'version' => '1.0.0'],
            ],
        ], $this->bearer($token)));

        $result = $this->rpcResult($initialize);
        $this->assertSame(self::HANDSHAKE, $result['protocolVersion']);
        $this->assertSame('flarum', $result['serverInfo']['name']);
        $this->assertStringContainsString('You are connected as', $result['instructions']);

        $session = $initialize->getHeaderLine('Mcp-Session-Id');
        $this->assertNotSame('', $session);

        $headers = [...$this->bearer($token), 'Mcp-Session-Id' => $session, 'MCP-Protocol-Version' => self::HANDSHAKE];

        $initialized = $this->send($this->mcpRequest(['jsonrpc' => '2.0', 'method' => 'notifications/initialized'], $headers));
        $this->assertSame(202, $initialized->getStatusCode());

        $list = $this->send($this->mcpRequest(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list'], $headers));

        $this->assertContains('search_discussions', $this->toolNames($list));
        $this->assertSame($session, $list->getHeaderLine('Mcp-Session-Id'));
    }

    #[Test]
    public function modern_era_discovers_and_lists_without_a_handshake(): void
    {
        $token = $this->developerToken(2);

        $discover = $this->rpcResult($this->modern('server/discover', [], $this->bearer($token)));

        $this->assertContains(self::MODERN, $discover['supportedVersions']);
        $this->assertArrayHasKey('tools', $discover['capabilities']);
        $this->assertStringContainsString('You are connected as', $discover['instructions']);

        $list = $this->modern('tools/list', [], $this->bearer($token));
        $names = $this->toolNames($list);

        foreach (['describe_api', 'list_resources', 'get_resource', 'search_discussions', 'read_discussion', 'whoami', 'start_discussion'] as $tool) {
            $this->assertContains($tool, $names);
        }
        $this->assertSame('', $list->getHeaderLine('Mcp-Session-Id'));
    }

    #[Test]
    public function modern_era_lists_resources_templates_and_prompts(): void
    {
        $token = $this->developerToken(2);

        $resources = $this->rpcResult($this->modern('resources/list', [], $this->bearer($token)));
        $this->assertContains('flarum://forum', array_column($resources['resources'], 'uri'));

        $templates = $this->rpcResult($this->modern('resources/templates/list', [], $this->bearer($token)));
        $this->assertEqualsCanonicalizing(
            ['flarum://discussions/{id}', 'flarum://discussions/{id}/posts/{offset}', 'flarum://discussions/{id}/posts/before/{before}', 'flarum://users/{username}'],
            array_column($templates['resourceTemplates'], 'uriTemplate')
        );

        $prompts = $this->rpcResult($this->modern('prompts/list', [], $this->bearer($token)));
        $this->assertEqualsCanonicalizing(['summarize_discussion', 'draft_reply'], array_column($prompts['prompts'], 'name'));

        $prompt = $this->rpcResult($this->modern('prompts/get', ['name' => 'summarize_discussion', 'arguments' => ['id' => '1']], $this->bearer($token), 'summarize_discussion'));
        $this->assertStringContainsString('read_discussion', $prompt['messages'][0]['content']['text']);
    }

    #[Test]
    public function get_is_not_allowed(): void
    {
        $response = $this->send($this->mcpRequest([], $this->bearer($this->developerToken(2)), 'GET'));

        $this->assertSame(405, $response->getStatusCode());
    }

    #[Test]
    public function a_foreign_origin_is_refused(): void
    {
        $response = $this->send($this->mcpRequest(
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'],
            [...$this->bearer($this->developerToken(2)), 'Origin' => 'https://evil.example']
        ));

        $this->assertSame(403, $response->getStatusCode());
    }

    #[Test]
    public function a_host_other_than_the_forum_is_refused(): void
    {
        $response = $this->send($this->mcpRequest(
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'],
            [...$this->bearer($this->developerToken(2)), 'Host' => 'attacker.example']
        ));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('Invalid Host', (string) $response->getBody());
    }

    #[Test]
    public function a_same_origin_browser_request_is_allowed(): void
    {
        $response = $this->send($this->mcpRequest(
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => [
                'protocolVersion' => self::HANDSHAKE,
                'capabilities' => new \stdClass(),
                'clientInfo' => ['name' => 'flarum-tests', 'version' => '1.0.0'],
            ]],
            [...$this->bearer($this->developerToken(2)), 'Origin' => 'http://localhost']
        )->withUri(new \Laminas\Diactoros\Uri('http://localhost/mcp')));

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function resource_metadata_describes_the_mcp_resource(): void
    {
        foreach (['/.well-known/oauth-protected-resource/mcp', '/.well-known/oauth-protected-resource'] as $path) {
            $response = $this->send($this->request('GET', $path));

            $this->assertSame(200, $response->getStatusCode());

            $metadata = $this->json($response);
            $this->assertSame('http://localhost/mcp', $metadata['resource']);
            $this->assertSame(['http://localhost'], $metadata['authorization_servers']);
            $this->assertSame(['mcp:read', 'mcp:write'], $metadata['scopes_supported']);
        }
    }
}
