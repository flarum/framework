<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\tools;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Mcp\Tests\integration\IssuesOAuthTokens;
use Flarum\Mcp\Tests\integration\McpTestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class NotificationsTest extends McpTestCase
{
    use IssuesOAuthTokens;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => [
                ['id' => 1, 'title' => 'One', 'user_id' => 2, 'comment_count' => 1],
                ['id' => 2, 'title' => 'Two', 'user_id' => 2, 'comment_count' => 1],
                ['id' => 3, 'title' => 'Three', 'user_id' => 2, 'comment_count' => 1],
            ],
            'notifications' => [
                ['id' => 1, 'user_id' => 2, 'type' => 'discussionRenamed', 'subject_id' => 1, 'from_user_id' => 1, 'read_at' => null, 'created_at' => Carbon::now()->subMinutes(3)],
                ['id' => 2, 'user_id' => 2, 'type' => 'discussionRenamed', 'subject_id' => 2, 'from_user_id' => 1, 'read_at' => Carbon::now(), 'created_at' => Carbon::now()->subMinutes(1)],
                ['id' => 3, 'user_id' => 2, 'type' => 'discussionRenamed', 'subject_id' => 3, 'from_user_id' => 1, 'read_at' => null, 'created_at' => Carbon::now()->subMinutes(2)],
                ['id' => 4, 'user_id' => 1, 'type' => 'discussionRenamed', 'subject_id' => 1, 'from_user_id' => 2, 'read_at' => null, 'created_at' => Carbon::now()],
            ],
        ]);
    }

    #[Test]
    public function lists_own_notifications_unread_first(): void
    {
        $list = $this->toolOutput($this->callTool('list_notifications', [], $this->bearer($this->developerToken(2))));

        $this->assertSame(['3', '1', '2'], array_column($list['data'], 'id'));
        $this->assertSame('admin', $list['data'][0]['from']);
        $this->assertSame(['type' => 'discussions', 'id' => '3'], $list['data'][0]['subject']);
    }

    #[Test]
    public function marks_given_or_all_notifications_read(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $this->toolOutput($this->callTool('mark_notifications_read', ['ids' => ['1']], $auth));
        $this->assertNotNull($this->database()->table('notifications')->where('id', 1)->value('read_at'));
        $this->assertNull($this->database()->table('notifications')->where('id', 3)->value('read_at'));

        $other = $this->toolOutput($this->callTool('mark_notifications_read', ['ids' => ['4']], $auth), expectError: true);
        $this->assertSame('4: Not found.', $other['error']);

        // A bad ID among good ones does not stop the rest.
        $mixed = $this->toolOutput($this->callTool('mark_notifications_read', ['ids' => ['4', '999', '3']], $auth));
        $this->assertSame(['3'], $mixed['marked']);
        $this->assertSame([['id' => '4', 'error' => 'Not found.'], ['id' => '999', 'error' => 'Not found.']], $mixed['failed']);
        $this->assertNotNull($this->database()->table('notifications')->where('id', 3)->value('read_at'));
        $this->assertNull($this->database()->table('notifications')->where('id', 4)->value('read_at'));

        $this->assertSame(['marked' => 'all'], $this->toolOutput($this->callTool('mark_notifications_read', [], $auth)));
        $this->assertSame(0, $this->database()->table('notifications')->where('user_id', 2)->whereNull('read_at')->count());
        $this->assertNull($this->database()->table('notifications')->where('id', 4)->value('read_at'));
    }

    #[Test]
    public function listing_notifications_marks_nothing_seen_or_read(): void
    {
        $this->setUpOAuth();
        $auth = $this->bearer($this->issueOAuthToken('mcp:read'));

        $this->assertSame(2, $this->counts()['new']);

        $list = $this->toolOutput($this->callTool('list_notifications', [], $auth));
        $this->assertSame(['3', '1', '2'], array_column($list['data'], 'id'));

        $this->assertNotificationsUntouched();
    }

    #[Test]
    public function notifications_about_what_the_actor_cannot_see_are_left_out(): void
    {
        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 4, 'title' => 'Hidden', 'user_id' => 1, 'comment_count' => 1, 'hidden_at' => Carbon::now()],
            ],
            'notifications' => [
                ['id' => 5, 'user_id' => 2, 'type' => 'discussionRenamed', 'subject_id' => 4, 'from_user_id' => 1, 'read_at' => null, 'created_at' => Carbon::now()],
            ],
        ]);

        $list = $this->toolOutput($this->callTool('list_notifications', [], $this->bearer($this->developerToken(2))));

        $this->assertSame(['3', '1', '2'], array_column($list['data'], 'id'));
    }

    #[Test]
    public function the_generic_tools_cannot_list_notifications(): void
    {
        $this->setUpOAuth();
        $auth = $this->bearer($this->issueOAuthToken('mcp:read'));

        $tools = array_column($this->rpcResult($this->modern('tools/list', [], $auth))['tools'], null, 'name');
        $this->assertNotContains('notifications', $tools['list_resources']['inputSchema']['properties']['type']['enum']);

        $response = $this->json($this->callTool('list_resources', ['type' => 'notifications'], $auth));
        $this->assertArrayNotHasKey('result', $response, (string) json_encode($response));

        // Reading one still works: Show has no side effect.
        $this->assertSame('1', $this->toolOutput($this->callTool('get_resource', ['type' => 'notifications', 'id' => '1'], $auth))['data']['id']);

        $this->assertNotificationsUntouched();
    }

    protected function assertNotificationsUntouched(): void
    {
        $this->assertNull($this->database()->table('users')->where('id', 2)->value('read_notifications_at'));
        $this->assertSame(['new' => 2, 'unread' => 2], $this->counts());
        $this->assertSame(2, $this->database()->table('notifications')->where('user_id', 2)->whereNull('read_at')->count());
    }

    /**
     * @return array{new: int, unread: int}
     */
    protected function counts(): array
    {
        $user = User::query()->findOrFail(2);

        return ['new' => $user->getNewNotificationCount(), 'unread' => $user->getUnreadNotificationCount()];
    }
}
