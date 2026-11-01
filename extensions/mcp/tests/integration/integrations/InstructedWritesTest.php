<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\integrations;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Mcp\Tests\integration\McpTestCase;
use Flarum\Post\Post;
use Flarum\Tags\Tag;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * The writes the instructions describe, done exactly as they describe them:
 * an instruction the API does not accept would send assistants astray.
 */
class InstructedWritesTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'flarum-approval', 'flarum-nicknames');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Tag::class => [
                ['id' => 1, 'name' => 'General', 'slug' => 'general', 'position' => 0, 'is_primary' => true],
                ['id' => 2, 'name' => 'Support', 'slug' => 'support', 'position' => 1, 'is_primary' => true],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Instructed', 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 2, 'last_post_number' => 2, 'is_approved' => false],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>First</p></t>', 'is_approved' => false],
                ['id' => 2, 'discussion_id' => 1, 'number' => 2, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Second</p></t>'],
            ],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
            ],
        ]);
    }

    #[Test]
    public function the_instructions_are_in_the_overview(): void
    {
        $overview = json_decode($this->rpcResult($this->modern('resources/read', ['uri' => 'flarum://forum'], $this->bearer($this->developerToken(1)), 'flarum://forum'))['contents'][0]['text'], true);
        $instructions = array_column($overview['extensions'], 'instructions', 'id');

        $this->assertStringContainsString('{"isHidden": true}', $overview['core']['instructions']);
        $this->assertStringContainsString('{"lastReadPostNumber": n}', $overview['core']['instructions']);
        $this->assertStringContainsString('relationships {"tags": [tag IDs]}', $instructions['flarum-tags']);
        $this->assertStringContainsString('approving a discussion\'s first post approves the discussion', $instructions['flarum-approval']);
        $this->assertStringContainsString('{"nickname": "..."}', $instructions['flarum-nicknames']);
    }

    #[Test]
    public function a_post_is_hidden_and_restored(): void
    {
        $admin = $this->bearer($this->developerToken(1));

        $this->toolOutput($this->callTool('update_resource', ['type' => 'posts', 'id' => '2', 'attributes' => ['isHidden' => true]], $admin));
        $this->assertNotNull(Post::query()->findOrFail(2)->hidden_at);

        $this->toolOutput($this->callTool('update_resource', ['type' => 'posts', 'id' => '2', 'attributes' => ['isHidden' => false]], $admin));
        $this->assertNull(Post::query()->findOrFail(2)->hidden_at);
    }

    #[Test]
    public function a_discussion_is_approved_and_retagged(): void
    {
        $admin = $this->bearer($this->developerToken(1));

        $this->toolOutput($this->callTool('update_resource', ['type' => 'posts', 'id' => '1', 'attributes' => ['isApproved' => true]], $admin));
        $this->assertTrue((bool) Discussion::query()->findOrFail(1)->is_approved);

        $this->toolOutput($this->callTool('update_resource', ['type' => 'discussions', 'id' => '1', 'relationships' => ['tags' => ['2']]], $admin));
        $this->assertSame([2], Discussion::query()->findOrFail(1)->tags()->pluck('tags.id')->all());
    }

    #[Test]
    public function discussions_are_marked_read(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $this->toolOutput($this->callTool('update_resource', ['type' => 'discussions', 'id' => '1', 'attributes' => ['lastReadPostNumber' => 2]], $auth));
        $this->assertSame(2, (int) $this->database()->table('discussion_user')->where(['discussion_id' => 1, 'user_id' => 2])->value('last_read_post_number'));

        $this->toolOutput($this->callTool('update_resource', ['type' => 'users', 'id' => '2', 'attributes' => ['markedAllAsReadAt' => Carbon::now()->toIso8601String()]], $auth));
        $this->assertTrue(Carbon::parse(User::query()->findOrFail(2)->marked_all_as_read_at)->isToday());
    }

    #[Test]
    public function a_nickname_is_set(): void
    {
        $this->toolOutput($this->callTool('update_resource', ['type' => 'users', 'id' => '1', 'attributes' => ['nickname' => 'The Admin']], $this->bearer($this->developerToken(1))));

        $this->assertSame('The Admin', User::query()->findOrFail(1)->nickname);
    }
}
