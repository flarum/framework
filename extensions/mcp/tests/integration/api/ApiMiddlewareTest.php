<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\api;

use Flarum\Api\Middleware\ThrottleApi;
use Flarum\Extend;
use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Flarum\Mcp\Testing\McpTestCase;
use Flarum\Mcp\Tests\integration\api\Fixtures\RecordsApiActor;
use Flarum\Mcp\Tests\integration\api\Fixtures\RefusesRequests;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use PHPUnit\Framework\Attributes\Test;

class ApiMiddlewareTest extends McpTestCase
{
    /** @var list<array{int|null, string|null, array<string, string>|null}> */
    public static array $seen = [];

    protected function setUp(): void
    {
        parent::setUp();

        static::$seen = [];
        RefusesRequests::$status = 422;

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
        ]);
    }

    #[Test]
    public function api_middleware_runs_as_the_bearer(): void
    {
        $this->extend((new Extend\Middleware('api'))->add(RecordsApiActor::class));

        $this->toolOutput($this->callTool('get_resource', ['type' => 'users', 'id' => '2'], $this->bearer($this->developerToken(2))));

        $this->assertSame([[2, 'users.show', ['id' => '2']]], static::$seen);
    }

    #[Test]
    public function an_error_response_from_middleware_is_a_tool_error(): void
    {
        $this->extend((new Extend\Middleware('api'))->add(RefusesRequests::class));

        $error = $this->toolOutput($this->callTool('get_resource', ['type' => 'users', 'id' => '2'], $this->bearer($this->developerToken(2))), expectError: true);

        $this->assertSame('`title`: Refused by middleware.', $error['error']);
    }

    #[Test]
    public function a_not_found_response_from_middleware_does_not_say_more(): void
    {
        RefusesRequests::$status = 404;
        $this->extend((new Extend\Middleware('api'))->add(RefusesRequests::class));

        $error = $this->toolOutput($this->callTool('get_resource', ['type' => 'users', 'id' => '2'], $this->bearer($this->developerToken(2))), expectError: true);

        $this->assertSame('Not found.', $error['error']);
    }

    #[Test]
    public function a_server_error_response_from_middleware_is_not_shown(): void
    {
        RefusesRequests::$status = 500;
        $this->extend((new Extend\Middleware('api'))->add(RefusesRequests::class));

        $response = $this->callTool('get_resource', ['type' => 'users', 'id' => '2'], $this->bearer($this->developerToken(2)));

        $this->assertStringNotContainsString('Refused by middleware.', (string) $response->getBody());
    }

    #[Test]
    public function middleware_the_api_client_skips_is_skipped(): void
    {
        $this->extend(
            (new Extend\Middleware('api'))->add(RecordsApiActor::class),
            new class implements ExtenderInterface {
                public function extend(Container $container, ?Extension $extension = null): void
                {
                    $container->extend('flarum.api_client.exclude_middleware', fn (array $exclude) => [...$exclude, RecordsApiActor::class]);
                }
            }
        );

        $this->toolOutput($this->callTool('get_resource', ['type' => 'users', 'id' => '2'], $this->bearer($this->developerToken(2))));

        $this->assertSame([], static::$seen);
    }

    #[Test]
    public function calls_are_throttled_even_where_the_api_client_skips_throttling(): void
    {
        $this->extend(
            (new Extend\ThrottleApi())->set('always', fn () => true),
            new class implements ExtenderInterface {
                public function extend(Container $container, ?Extension $extension = null): void
                {
                    $container->extend('flarum.api_client.exclude_middleware', fn (array $exclude) => [...$exclude, ThrottleApi::class]);
                }
            }
        );

        $error = $this->toolOutput($this->callTool('get_resource', ['type' => 'users', 'id' => '2'], $this->bearer($this->developerToken(2))), expectError: true);

        $this->assertSame('You are doing that too often; wait before trying again.', $error['error']);
    }
}
