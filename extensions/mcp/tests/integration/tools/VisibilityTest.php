<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\tools;

use Flarum\Discussion\Discussion;
use Flarum\Mcp\Tests\integration\McpTestCase;
use Flarum\Post\Post;
use Flarum\Tags\Tag;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class VisibilityTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Tag::class => [
                ['id' => 1, 'name' => 'General', 'slug' => 'general', 'position' => 0],
                ['id' => 2, 'name' => 'Staff', 'slug' => 'staff', 'position' => 1, 'is_restricted' => true],
            ],
        ]);

        $this->database()->rollBack();

        // Discussions and posts go in outside the test's transaction: MySQL
        // and MariaDB leave uncommitted rows out of FULLTEXT search. They are
        // deleted again in tearDown().
        $this->database()->table('discussions')->insert($this->rowsThroughFactory(Discussion::class, [
            ['id' => 1, 'title' => 'Public plans', 'slug' => 'public-plans', 'user_id' => 1, 'comment_count' => 1, 'last_post_number' => 1],
            ['id' => 2, 'title' => 'Secret plans', 'slug' => 'secret-plans', 'user_id' => 1, 'comment_count' => 1, 'last_post_number' => 1],
        ]));

        $this->database()->table('posts')->insert($this->rowsThroughFactory(Post::class, [
            ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Everyone can read the plans</p></t>'],
            ['id' => 2, 'discussion_id' => 2, 'number' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Only staff can read the plans</p></t>'],
        ]));

        // Each table points at the other, so first posts are set afterwards.
        foreach ([1, 2] as $id) {
            $this->database()->table('discussions')->where('id', $id)->update(['first_post_id' => $id]);
        }

        // The rollback also undid the rows populated when the app booted.
        $this->database()->beginTransaction();

        $this->prepareDatabase([
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
                ['discussion_id' => 2, 'tag_id' => 2],
            ],
        ]);

        $this->populateDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->database()->table('posts')->whereIn('id', [1, 2])->delete();
        $this->database()->table('discussions')->whereIn('id', [1, 2])->delete();
    }

    #[Test]
    public function a_member_cannot_see_a_restricted_discussion(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $list = $this->toolOutput($this->callTool('list_resources', ['type' => 'discussions'], $auth));
        $this->assertSame(['1'], array_column($list['data'], 'id'));

        $search = $this->toolOutput($this->callTool('search_discussions', [], $auth));
        $this->assertSame(['1'], array_column($search['data'], 'id'));
        $this->assertSame('Everyone can read the plans', $search['data'][0]['excerpt']);

        $query = $this->toolOutput($this->callTool('search_discussions', ['query' => 'plans'], $auth));
        $this->assertSame(['1'], array_column($query['data'], 'id'));

        $get = $this->toolOutput($this->callTool('get_resource', ['type' => 'discussions', 'id' => '2'], $auth), expectError: true);
        $this->assertSame('Not found.', $get['error']);

        $read = $this->toolOutput($this->callTool('read_discussion', ['id' => '2'], $auth), expectError: true);
        $this->assertSame('Not found.', $read['error']);

        $resource = $this->json($this->modern('resources/read', ['uri' => 'flarum://discussions/2'], $auth, 'flarum://discussions/2'));
        $this->assertSame(-32602, $resource['error']['code']);
    }

    #[Test]
    public function an_admin_sees_the_restricted_discussion(): void
    {
        $auth = $this->bearer($this->developerToken(1));

        $list = $this->toolOutput($this->callTool('list_resources', ['type' => 'discussions', 'sort' => 'createdAt'], $auth));
        $this->assertEqualsCanonicalizing(['1', '2'], array_column($list['data'], 'id'));

        $search = $this->toolOutput($this->callTool('search_discussions', ['query' => 'plans'], $auth));
        $this->assertEqualsCanonicalizing(['1', '2'], array_column($search['data'], 'id'));

        $get = $this->toolOutput($this->callTool('get_resource', ['type' => 'discussions', 'id' => '2'], $auth));
        $this->assertSame('Secret plans', $get['data']['title']);

        $read = $this->toolOutput($this->callTool('read_discussion', ['id' => '2'], $auth));
        $this->assertSame('Secret plans', $read['discussion']['title']);
        $this->assertSame('Only staff can read the plans', $read['posts'][0]['content']);
        $this->assertSame('http://localhost/d/2-secret-plans/1', $read['posts'][0]['url']);

        $resource = $this->rpcResult($this->modern('resources/read', ['uri' => 'flarum://discussions/2'], $auth, 'flarum://discussions/2'));
        $this->assertSame('Secret plans', json_decode($resource['contents'][0]['text'], true)['discussion']['title']);
    }

    #[Test]
    public function describe_api_offers_admin_only_types_to_admins_only(): void
    {
        $member = $this->toolOutput($this->callTool('describe_api', [], $this->bearer($this->developerToken(2))));
        $admin = $this->toolOutput($this->callTool('describe_api', [], $this->bearer($this->developerToken(1))));

        $memberTypes = array_column($member['types'], 'type');
        $adminTypes = array_column($admin['types'], 'type');

        $this->assertContains('discussions', $memberTypes);
        $this->assertContains('tags', $memberTypes);
        $this->assertNotContains('access-tokens', $adminTypes);
        $this->assertNotContains('system-info', $adminTypes);

        $adminOnly = array_values(array_diff($adminTypes, $memberTypes));
        $this->assertContains('oauth-provider-clients', $adminOnly);
    }
}
