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
use Flarum\Mcp\Api\ResourceExposure;
use Flarum\Mcp\Extend\Mcp;
use Flarum\Mcp\Overview\ExtensionOwnership;
use Flarum\Mcp\Tests\integration\McpTestCase;
use Flarum\Mcp\Tests\integration\protocol\Fixtures\CountingExposure;
use Flarum\Mcp\Tests\integration\protocol\Fixtures\CountingOwnership;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\Attributes\Test;

/**
 * What a request computes, and how often.
 */
class RequestCostTest extends McpTestCase
{
    /**
     * @var list<string>
     */
    public static array $calls = [];

    protected function setUp(): void
    {
        parent::setUp();

        static::$calls = [];

        $this->extend(
            (new Mcp())->instructions(function () {
                RequestCostTest::$calls[] = 'instructions';

                return 'Counted.';
            }),
            new class implements ExtenderInterface {
                public function extend(Container $container, ?Extension $extension = null): void
                {
                    $container->singleton(ResourceExposure::class, CountingExposure::class);
                    $container->singleton(ExtensionOwnership::class, CountingOwnership::class);
                }
            },
        );

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
        ]);
    }

    #[Test]
    public function instructions_are_built_only_for_initialize_and_discover(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $this->rpcResult($this->modern('tools/list', [], $auth));
        $this->rpcResult($this->modern('resources/list', [], $auth));
        $this->assertNotContains('instructions', static::$calls);

        $this->assertStringContainsString('Counted.', $this->rpcResult($this->modern('server/discover', [], $auth))['instructions']);
        $this->assertSame(1, $this->callCount('instructions'));

        $session = $this->initializeSession($auth);
        $this->assertSame(2, $this->callCount('instructions'));

        $batch = $this->send($this->mcpRequest([
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'],
            ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list'],
        ], [...$auth, 'Mcp-Session-Id' => $session, 'MCP-Protocol-Version' => self::HANDSHAKE]));
        $this->assertSame(200, $batch->getStatusCode());
        $this->assertSame(2, $this->callCount('instructions'));
    }

    #[Test]
    public function exposure_and_ownership_are_computed_once_per_request(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $this->rpcResult($this->modern('server/discover', [], $auth));

        $exposures = array_filter(static::$calls, fn ($call) => str_starts_with($call, 'operations:'));
        $this->assertNotEmpty($exposures);
        $this->assertSame(1, max(array_count_values($exposures)), 'Each type is exposed once per actor.');
        $this->assertSame(1, $this->callCount('namespaces'));

        static::$calls = [];
        $this->rpcResult($this->modern('server/discover', [], $auth));

        // A new request has a new actor, so exposure is worked out again.
        $this->assertSame(1, max(array_count_values(array_filter(static::$calls, fn ($call) => str_starts_with($call, 'operations:')))));
    }

    #[Test]
    public function a_preflight_builds_nothing(): void
    {
        $response = $this->send($this->request('OPTIONS', '/mcp')
            ->withUri(new Uri('http://localhost/mcp'))
            ->withHeader('Host', 'localhost')
            ->withHeader('Origin', 'http://localhost')
            ->withHeader('Access-Control-Request-Method', 'POST'));

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame([], static::$calls);
    }

    protected function callCount(string $call): int
    {
        return array_count_values(static::$calls)[$call] ?? 0;
    }
}
