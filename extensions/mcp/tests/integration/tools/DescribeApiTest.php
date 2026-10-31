<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\tools;

use Flarum\Mcp\Tests\integration\McpTestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class DescribeApiTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
        ]);
    }

    #[Test]
    public function field_descriptions_are_surfaced(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $discussions = $this->toolOutput($this->callTool('describe_api', ['type' => 'discussions'], $auth));
        $attributes = array_column($discussions['attributes'], null, 'name');
        $relationships = array_column($discussions['relationships'], null, 'name');

        // Core's own fields.
        $this->assertSame('Number of visible comment posts, including the first post.', $attributes['commentCount']['description']);
        $this->assertSame('The user who started the discussion.', $relationships['user']['description']);
        $this->assertArrayNotHasKey('description', $attributes['title']);

        // A field an extension adds, and the type description it gives through Extend\Mcp.
        $this->assertStringStartsWith('The discussion\'s tags.', $relationships['tags']['description']);
        $this->assertStringStartsWith('Tags that discussions are filed under.', $this->toolOutput($this->callTool('describe_api', ['type' => 'tags'], $auth))['description']);
    }
}
