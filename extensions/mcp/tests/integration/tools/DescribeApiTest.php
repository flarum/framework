<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\tools;

use Flarum\Group\Group;
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

    #[Test]
    public function fields_the_actor_cannot_see_are_not_listed(): void
    {
        $admin = $this->bearer($this->developerToken(1));
        $member = $this->bearer($this->developerToken(2));

        $attributes = fn (array $auth, string $type) => array_column(
            $this->toolOutput($this->callTool('describe_api', ['type' => $type], $auth))['attributes'],
            'name'
        );

        // Visible to admins only: decided by the actor alone.
        $this->assertContains('version', $attributes($admin, 'forums'));
        $this->assertContains('adminUrl', $attributes($admin, 'forums'));
        $this->assertNotContains('version', $attributes($member, 'forums'));
        $this->assertNotContains('adminUrl', $attributes($member, 'forums'));
        $this->assertContains('title', $attributes($member, 'forums'));

        // editCredentials decides email for admins; for a member it hangs on the user, so it is left out.
        $this->assertContains('email', $attributes($admin, 'users'));
        $this->assertNotContains('email', $attributes($member, 'users'));
        $this->assertContains('username', $attributes($member, 'users'));

        // Never visible to anyone.
        $this->assertNotContains('password', $attributes($admin, 'users'));

        // The summary lists the same fields.
        $summaries = array_column($this->toolOutput($this->callTool('describe_api', [], $member))['types'], null, 'type');
        $this->assertNotContains('version', $summaries['forums']['attributes']);
        $this->assertNotContains('email', $summaries['users']['attributes']);
    }

    #[Test]
    public function operations_the_actor_is_refused_are_not_listed(): void
    {
        $operations = fn (int $userId) => array_column(
            $this->toolOutput($this->callTool('describe_api', [], $this->bearer($this->developerToken($userId))))['types'],
            'operations',
            'type'
        );

        // can('createGroup') is decided without a model.
        $this->assertContains('create', $operations(1)['groups']);
        $this->assertNotContains('create', $operations(2)['groups']);

        // can('edit') needs the group: left to the call.
        $this->assertContains('update', $operations(2)['groups']);
        $this->assertContains('create', $operations(2)['discussions']);
    }

    #[Test]
    public function guests_are_not_offered_authenticated_operations(): void
    {
        $this->prepareDatabase([
            'group_permission' => [['group_id' => Group::GUEST_ID, 'permission' => 'mcp.use']],
        ]);

        $types = array_column($this->toolOutput($this->callTool('describe_api'))['types'], 'operations', 'type');

        $this->assertEqualsCanonicalizing(['index', 'show'], $types['discussions']);
        $this->assertArrayNotHasKey('notifications', $types);
    }
}
