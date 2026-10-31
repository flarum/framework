<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Str;
use Laminas\Diactoros\StreamFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Drives /mcp with real JSON-RPC bodies, in either protocol era.
 */
abstract class McpTestCase extends TestCase
{
    use RetrievesAuthorizedUsers;

    public const string MODERN = '2026-07-28';
    public const string HANDSHAKE = '2025-11-25';

    protected int $nextId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-oauth-provider', 'flarum-mcp');
    }

    /**
     * A POST to /mcp. The body is a real seekable stream: the forum's JSON body
     * parser reads it before the MCP transport does.
     *
     * @param array<string, mixed>|list<array<string, mixed>>|string $body
     * @param array<string, string> $headers
     */
    protected function mcpRequest(array|string $body, array $headers = [], string $method = 'POST'): ServerRequestInterface
    {
        $request = $this->request($method, '/mcp')
            ->withHeader('Host', 'localhost')
            ->withHeader('Accept', 'application/json, text/event-stream');

        if ($method === 'POST') {
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody((new StreamFactory())->createStream(is_string($body) ? $body : json_encode($body, JSON_THROW_ON_ERROR)));
        }

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }

    /**
     * A modern-era (2026-07-28) request: the `_meta` envelope plus the
     * Mcp-Method / Mcp-Name / MCP-Protocol-Version headers.
     *
     * @param array<string, mixed> $params
     * @param array<string, string> $headers
     */
    protected function modern(string $method, array $params = [], array $headers = [], ?string $name = null): ResponseInterface
    {
        $params['_meta'] = [
            'io.modelcontextprotocol/protocolVersion' => self::MODERN,
            'io.modelcontextprotocol/clientCapabilities' => new \stdClass(),
            'io.modelcontextprotocol/clientInfo' => ['name' => 'flarum-tests', 'version' => '1.0.0'],
        ];

        $headers = [
            'MCP-Protocol-Version' => self::MODERN,
            'Mcp-Method' => $method,
            ...($name !== null ? ['Mcp-Name' => $name] : []),
            ...$headers,
        ];

        return $this->send($this->mcpRequest([
            'jsonrpc' => '2.0',
            'id' => $this->nextId++,
            'method' => $method,
            'params' => $params,
        ], $headers));
    }

    /**
     * @param array<string, mixed> $arguments
     * @param array<string, string> $headers
     */
    protected function callTool(string $tool, array $arguments = [], array $headers = []): ResponseInterface
    {
        return $this->modern('tools/call', [
            'name' => $tool,
            'arguments' => $arguments ?: new \stdClass(),
        ], $headers, $tool);
    }

    /**
     * Runs the handshake-era `initialize` and returns the session ID.
     *
     * @param array<string, string> $headers
     */
    protected function initializeSession(array $headers = []): string
    {
        $response = $this->send($this->mcpRequest([
            'jsonrpc' => '2.0',
            'id' => $this->nextId++,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => self::HANDSHAKE,
                'capabilities' => new \stdClass(),
                'clientInfo' => ['name' => 'flarum-tests', 'version' => '1.0.0'],
            ],
        ], $headers));

        $this->rpcResult($response);
        $session = $response->getHeaderLine('Mcp-Session-Id');
        $this->assertNotSame('', $session);

        return $session;
    }

    /**
     * A handshake-era request inside a session.
     *
     * @param array<string, mixed> $params
     * @param array<string, string> $headers
     */
    protected function inSession(string $session, string $method, array $params = [], array $headers = []): ResponseInterface
    {
        return $this->send($this->mcpRequest([
            'jsonrpc' => '2.0',
            'id' => $this->nextId++,
            'method' => $method,
            'params' => $params ?: new \stdClass(),
        ], [...$headers, 'Mcp-Session-Id' => $session, 'MCP-Protocol-Version' => self::HANDSHAKE]));
    }

    /**
     * The `result` of a successful JSON-RPC response.
     *
     * @return array<string, mixed>
     */
    protected function rpcResult(ResponseInterface $response): array
    {
        $body = $this->json($response);

        $this->assertSame(200, $response->getStatusCode(), (string) json_encode($body));
        $this->assertArrayHasKey('result', $body, (string) json_encode($body));

        return $body['result'];
    }

    /**
     * The decoded JSON text of a tool result.
     *
     * @return array<string, mixed>
     */
    protected function toolOutput(ResponseInterface $response, bool $expectError = false): array
    {
        $result = $this->rpcResult($response);

        $this->assertSame($expectError, $result['isError'] ?? false, (string) json_encode($result));

        $text = $result['content'][0]['text'] ?? '';

        if ($expectError) {
            return ['error' => $text];
        }

        $decoded = json_decode($text, true);

        return is_array($decoded) ? $decoded : ['text' => $text];
    }

    /**
     * @return list<string>
     */
    protected function toolNames(ResponseInterface $response): array
    {
        return array_column($this->rpcResult($response)['tools'], 'name');
    }

    /**
     * @return array<string, mixed>
     */
    protected function json(ResponseInterface $response): array
    {
        return (array) json_decode((string) $response->getBody(), true);
    }

    protected function bearer(string $token): array
    {
        return ['Authorization' => "Bearer $token"];
    }

    protected function developerToken(int $userId): string
    {
        $token = Str::random(40);

        $this->database()->table('access_tokens')->insert([
            'token' => $token,
            'user_id' => $userId,
            'created_at' => Carbon::now()->toDateTimeString(),
            'last_activity_at' => Carbon::now()->toDateTimeString(),
            'type' => 'developer',
        ]);

        return $token;
    }

    protected function sessionToken(int $userId): string
    {
        $token = Str::random(40);

        $this->database()->table('access_tokens')->insert([
            'token' => $token,
            'user_id' => $userId,
            'created_at' => Carbon::now()->toDateTimeString(),
            'last_activity_at' => Carbon::now()->toDateTimeString(),
            'type' => 'session',
        ]);

        return $token;
    }
}
