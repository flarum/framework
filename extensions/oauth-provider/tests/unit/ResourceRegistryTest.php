<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\unit;

use Flarum\Http\UrlGenerator;
use Flarum\OAuthProvider\ResourceServer\ResourceRegistry;
use Illuminate\Container\Container;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ResourceRegistryTest extends TestCase
{
    protected function registry(): ResourceRegistry
    {
        $container = new Container();
        $container->instance(UrlGenerator::class, $this->createStub(UrlGenerator::class));

        $registry = new ResourceRegistry($container);
        $registry->register(fn (UrlGenerator $url) => 'https://forum.example.com/mcp', ['mcp:read', 'mcp:write']);

        return $registry;
    }

    #[Test]
    public function resolves_registered_resources(): void
    {
        $registry = $this->registry();

        $this->assertSame(['https://forum.example.com/mcp' => ['mcp:read', 'mcp:write']], $registry->all());
        $this->assertTrue($registry->has('https://forum.example.com/mcp'));
        $this->assertFalse($registry->has('https://forum.example.com/other'));
        $this->assertNull($registry->scopesFor('https://forum.example.com/other'));
    }

    #[Test]
    public function allows_the_resource_scopes_plus_oidc_scopes(): void
    {
        $registry = $this->registry();

        $this->assertTrue($registry->allowsScopes('https://forum.example.com/mcp', ['openid', 'profile', 'mcp:read']));
        $this->assertTrue($registry->allowsScopes('https://forum.example.com/mcp', []));
        $this->assertFalse($registry->allowsScopes('https://forum.example.com/mcp', ['mcp:read', 'tasks:read']));
        $this->assertFalse($registry->allowsScopes('https://forum.example.com/other', ['mcp:read']));
    }
}
