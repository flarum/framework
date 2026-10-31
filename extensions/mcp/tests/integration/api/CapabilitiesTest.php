<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\api;

use Flarum\Mcp\Tests\integration\McpTestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class CapabilitiesTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-likes');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
        ]);
    }

    #[Test]
    public function admins_get_every_capability_with_its_source_and_access(): void
    {
        $response = $this->send($this->request('GET', '/api/mcp-capabilities', ['authenticatedAs' => 1]));

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());

        $capabilities = array_column($this->json($response)['data'], 'attributes', 'id');

        $this->assertSame('tool', $capabilities['tool:search_discussions']['kind']);
        $this->assertTrue($capabilities['tool:search_discussions']['readOnly']);
        $this->assertSame('flarum-mcp', $capabilities['tool:search_discussions']['extension']);
        $this->assertSame('MCP', $capabilities['tool:search_discussions']['extensionTitle']);

        $this->assertFalse($capabilities['tool:like_post']['readOnly']);
        $this->assertFalse($capabilities['tool:like_post']['destructive']);
        $this->assertTrue($capabilities['tool:delete_resource']['destructive']);

        $this->assertSame('resource', $capabilities['resource:flarum://forum']['kind']);
        $this->assertNull($capabilities['resource:flarum://forum']['readOnly']);
        $this->assertSame('template', $capabilities['template:flarum://discussions/{id}']['kind']);
        $this->assertSame('prompt', $capabilities['prompt:summarize_discussion']['kind']);
    }

    #[Test]
    public function members_are_forbidden(): void
    {
        $response = $this->send($this->request('GET', '/api/mcp-capabilities', ['authenticatedAs' => 2]));

        $this->assertSame(403, $response->getStatusCode());
    }

    #[Test]
    public function guests_are_forbidden(): void
    {
        // admin() alone, with no authenticated(): a guest fails the admin check (403), not sign-in (401).
        $response = $this->send($this->request('GET', '/api/mcp-capabilities'));

        $this->assertSame(403, $response->getStatusCode());
    }

    #[Test]
    public function the_type_is_not_offered_through_mcp(): void
    {
        $types = array_column(
            $this->toolOutput($this->callTool('describe_api', [], $this->bearer($this->developerToken(1))))['types'],
            'type'
        );

        $this->assertContains('discussions', $types);
        $this->assertNotContains('mcp-capabilities', $types);
    }

    #[Test]
    public function the_admin_page_renders(): void
    {
        $response = $this->send($this->request('GET', '/admin', ['authenticatedAs' => 1]));

        $this->assertSame(200, $response->getStatusCode());
    }
}
