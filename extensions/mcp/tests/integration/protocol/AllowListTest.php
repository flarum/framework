<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\protocol;

use Flarum\Mcp\Extend\Mcp;
use Flarum\Mcp\Tests\integration\IssuesOAuthTokens;
use Flarum\Mcp\Tests\integration\McpTestCase;
use Flarum\User\User;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\Attributes\Test;

class AllowListTest extends McpTestCase
{
    use IssuesOAuthTokens;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extend(
            (new Mcp())
                ->allowHosts(['forum.example.org'])
                ->allowOrigins(['https://app.example.com'])
        );

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
        ]);
    }

    /**
     * @param array<string, string> $headers
     */
    protected function ping(array $headers): \Psr\Http\Message\ResponseInterface
    {
        return $this->send($this->mcpRequest(
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'],
            [...$this->bearer($this->developerToken(2)), 'MCP-Protocol-Version' => self::HANDSHAKE, ...$headers]
        ));
    }

    #[Test]
    public function an_allowed_host_is_answered_besides_the_forum_host(): void
    {
        $this->assertNotSame(403, $this->ping(['Host' => 'forum.example.org'])->getStatusCode());
        $this->assertNotSame(403, $this->ping(['Host' => 'localhost'])->getStatusCode());
        $this->assertSame(403, $this->ping(['Host' => 'attacker.example'])->getStatusCode());
    }

    #[Test]
    public function an_allowed_origin_may_call_from_the_browser(): void
    {
        $this->assertNotSame(403, $this->ping(['Origin' => 'https://app.example.com'])->getStatusCode());
        $this->assertSame(403, $this->ping(['Origin' => 'https://evil.example'])->getStatusCode());

        $preflight = $this->send($this->request('OPTIONS', '/mcp')
            ->withUri(new Uri('http://localhost/mcp'))
            ->withHeader('Host', 'localhost')
            ->withHeader('Origin', 'https://app.example.com')
            ->withHeader('Access-Control-Request-Method', 'POST'));

        $this->assertSame(204, $preflight->getStatusCode());
        $this->assertSame('https://app.example.com', $preflight->getHeaderLine('Access-Control-Allow-Origin'));
    }

    #[Test]
    public function an_allowed_origin_can_read_the_sign_in_challenge(): void
    {
        $response = $this->send($this->mcpRequest(
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'],
            ['MCP-Protocol-Version' => self::HANDSHAKE, 'Origin' => 'https://app.example.com']
        ));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('https://app.example.com', $response->getHeaderLine('Access-Control-Allow-Origin'), 'The browser must be allowed to read the 401');
        $this->assertStringContainsStringIgnoringCase('WWW-Authenticate', $response->getHeaderLine('Access-Control-Expose-Headers'));
    }

    #[Test]
    public function an_allowed_origin_can_read_a_scope_step_up(): void
    {
        $this->setUpOAuth();
        $jwt = $this->issueOAuthToken('mcp:read');

        $response = $this->callTool('start_discussion', ['title' => 'Hello', 'content' => 'World'], [
            ...$this->bearer($jwt),
            'Origin' => 'https://app.example.com',
        ]);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('https://app.example.com', $response->getHeaderLine('Access-Control-Allow-Origin'), 'The browser must be allowed to read the 403');
        $this->assertStringContainsStringIgnoringCase('WWW-Authenticate', $response->getHeaderLine('Access-Control-Expose-Headers'));
    }

    #[Test]
    public function an_origin_that_is_not_allowed_gets_no_cors_grant_on_a_challenge(): void
    {
        $response = $this->send($this->mcpRequest(
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'],
            ['MCP-Protocol-Version' => self::HANDSHAKE, 'Origin' => 'https://evil.example']
        ));

        $this->assertSame('', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    #[Test]
    public function a_client_without_an_origin_gets_a_plain_challenge(): void
    {
        $response = $this->send($this->mcpRequest(
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'],
            ['MCP-Protocol-Version' => self::HANDSHAKE]
        ));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('resource_metadata=', $response->getHeaderLine('WWW-Authenticate'));
        $this->assertSame('', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }
}
