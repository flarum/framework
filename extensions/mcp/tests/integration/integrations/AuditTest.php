<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\integrations;

use Flarum\Audit\AuditLog;
use Flarum\Audit\Testing\InteractsWithAuditLog;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Mcp\Testing\IssuesOAuthTokens;
use Flarum\Mcp\Testing\McpTestCase;
use Flarum\Post\Post;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class AuditTest extends McpTestCase
{
    use InteractsWithAuditLog;
    use IssuesOAuthTokens;

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            $this->tearDownAuditLog();
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAuditLog();

        $this->extension('flarum-audit', 'flarum-flags');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => [
                ['id' => 1, 'title' => 'Flag me', 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'last_post_number' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Buy now</p></t>'],
            ],
            'group_permission' => [
                ['permission' => 'discussion.flagPosts', 'group_id' => Group::MEMBER_ID],
            ],
        ]);
    }

    #[Test]
    public function writes_are_logged_as_the_bearer_of_a_developer_token(): void
    {
        $this->flagThrough($this->bearer($this->developerToken(2)));
    }

    #[Test]
    public function writes_are_logged_as_the_bearer_of_an_oauth_token(): void
    {
        $this->setUpOAuth();

        $this->flagThrough($this->bearer($this->issueOAuthToken('mcp:read mcp:write')));
    }

    /**
     * @param array<string, string> $auth
     */
    protected function flagThrough(array $auth): void
    {
        $this->toolOutput($this->callTool('flag_post', ['postId' => '1', 'reason' => 'spam'], $auth));

        $this->assertLogExists('post.flagged', ['discussion_id' => 1, 'post_id' => 1, 'reason' => 'spam'], 2);
        $this->assertSame('mcp', AuditLog::query()->where('action', 'post.flagged')->value('client'));
    }
}
