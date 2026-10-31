<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\protocol;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Flarum\Group\Group;
use Flarum\Mcp\Tests\integration\McpTestCase;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\AbstractLogger;
use Stringable;

/**
 * Handshake-era sessions, what the server advertises, and where the SDK logs.
 */
class SessionTest extends McpTestCase
{
    /**
     * @var list<string>
     */
    public static array $logged = [];

    protected function setUp(): void
    {
        parent::setUp();

        static::$logged = [];

        $this->extend(new class implements ExtenderInterface {
            public function extend(Container $container, ?Extension $extension = null): void
            {
                $container->instance('log', new class extends AbstractLogger {
                    public function log($level, string|Stringable $message, array $context = []): void
                    {
                        SessionTest::$logged[] = (string) $message;
                    }
                });
            }
        });

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
        ]);
    }

    #[Test]
    public function only_supported_capabilities_are_advertised_in_both_eras(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $initialize = $this->rpcResult($this->send($this->mcpRequest([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => self::HANDSHAKE,
                'capabilities' => new \stdClass(),
                'clientInfo' => ['name' => 'flarum-tests', 'version' => '1.0.0'],
            ],
        ], $auth)));
        $discover = $this->rpcResult($this->modern('server/discover', [], $auth));

        foreach (['initialize' => $initialize['capabilities'], 'server/discover' => $discover['capabilities']] as $method => $capabilities) {
            $this->assertEqualsCanonicalizing(['tools', 'resources', 'prompts'], array_keys($capabilities), $method);
            $this->assertSame([], (array) $capabilities['resources'], $method);
            $this->assertSame([], (array) $capabilities['tools'], $method);
        }
    }

    #[Test]
    public function delete_ends_the_session(): void
    {
        $auth = $this->bearer($this->developerToken(2));
        $session = $this->initializeSession($auth);

        $this->assertSame(200, $this->inSession($session, 'tools/list', [], $auth)->getStatusCode());

        $this->assertSame(200, $this->delete($session, $auth)->getStatusCode());
        $this->assertSame(404, $this->inSession($session, 'tools/list', [], $auth)->getStatusCode());

        $this->assertSame(400, $this->send($this->mcpRequest([], $auth, 'DELETE'))->getStatusCode());
    }

    #[Test]
    public function a_session_belongs_to_the_bearer_that_opened_it(): void
    {
        $owner = $this->bearer($this->developerToken(2));
        $other = $this->bearer($this->developerToken(1));
        $session = $this->initializeSession($owner);

        $hijack = $this->inSession($session, 'tools/list', [], $other);
        $this->assertSame(404, $hijack->getStatusCode());
        $this->assertArrayNotHasKey('result', $this->json($hijack));

        // A guest presenting it is refused too.
        $this->database()->table('group_permission')->insert(['group_id' => Group::GUEST_ID, 'permission' => 'mcp.use']);
        $this->assertSame(404, $this->inSession($session, 'tools/list')->getStatusCode());

        // Nor can anyone else end it.
        $this->delete($session, $other);
        $this->assertSame(200, $this->inSession($session, 'tools/list', [], $owner)->getStatusCode());
    }

    #[Test]
    public function the_sdk_logs_through_flarum(): void
    {
        $this->rpcResult($this->modern('tools/list', [], $this->bearer($this->developerToken(2))));

        $this->assertNotEmpty(array_filter(static::$logged, fn ($message) => str_starts_with($message, '[mcp] ')));
    }

    /**
     * @param array<string, string> $headers
     */
    protected function delete(string $session, array $headers): ResponseInterface
    {
        return $this->send($this->mcpRequest([], [...$headers, 'Mcp-Session-Id' => $session, 'MCP-Protocol-Version' => self::HANDSHAKE], 'DELETE'));
    }
}
