<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\integrations;

use Carbon\Carbon;
use Flarum\Mcp\Tests\integration\McpTestCase;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class MessagesTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'bob', 'email' => 'bob@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'carol', 'email' => 'carol@machine.local', 'is_email_confirmed' => 1],
            ],
            Dialog::class => [
                ['id' => 1, 'type' => 'direct', 'first_message_id' => 1, 'last_message_id' => 2, 'last_message_at' => Carbon::now()->subHour()],
            ],
            DialogMessage::class => [
                ['id' => 1, 'dialog_id' => 1, 'user_id' => 3, 'content' => '<t><p>Hi normal</p></t>', 'number' => 1, 'created_at' => Carbon::now()->subHours(2)],
                ['id' => 2, 'dialog_id' => 1, 'user_id' => 2, 'content' => '<t><p>Hi Bob</p></t>', 'number' => 2, 'created_at' => Carbon::now()->subHour()],
            ],
            'dialog_user' => [
                ['dialog_id' => 1, 'user_id' => 2, 'joined_at' => Carbon::now(), 'last_read_message_id' => 2],
                ['dialog_id' => 1, 'user_id' => 3, 'joined_at' => Carbon::now(), 'last_read_message_id' => 1],
            ],
        ]);
    }

    #[Test]
    public function conversations_are_listed_and_read_oldest_first(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $list = $this->toolOutput($this->callTool('list_conversations', [], $auth));
        $this->assertSame(['1'], array_column($list['data'], 'id'));
        $this->assertSame(['bob'], $list['data'][0]['with']);
        $this->assertSame('Hi Bob', $list['data'][0]['lastMessage']['content']);
        $this->assertSame('http://localhost/messages/dialog/1', $list['data'][0]['url']);

        $read = $this->toolOutput($this->callTool('read_conversation', ['id' => '1'], $auth));
        $this->assertSame([1, 2], array_column($read['messages'], 'number'));
        $this->assertSame(['bob', 'normal'], array_column($read['messages'], 'author'));
        $this->assertSame('Hi normal', $read['messages'][0]['content']);
        $this->assertNull($read['paging']['older']);

        // Carol is not in the conversation.
        $hidden = $this->toolOutput($this->callTool('read_conversation', ['id' => '1'], $this->bearer($this->developerToken(4))), expectError: true);
        $this->assertSame('Not found.', $hidden['error']);
    }

    #[Test]
    public function send_message_replies_in_a_conversation(): void
    {
        $reply = $this->toolOutput($this->callTool('send_message', ['conversationId' => '1', 'content' => 'Still there?'], $this->bearer($this->developerToken(2))));

        $this->assertSame('1', $reply['conversationId']);
        $this->assertSame(3, $reply['number']);
        $this->assertSame(2, DialogMessage::query()->findOrFail($reply['messageId'])->user_id);
    }

    #[Test]
    public function send_message_writes_to_a_member(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $new = $this->toolOutput($this->callTool('send_message', ['to' => 'carol', 'content' => 'Hello Carol'], $auth));
        $this->assertNotSame('1', $new['conversationId']);
        $this->assertSame('http://localhost/messages/dialog/'.$new['conversationId'], $new['url']);

        $message = DialogMessage::query()->findOrFail($new['messageId']);
        $this->assertSame(2, $message->user_id);
        $this->assertEqualsCanonicalizing([2, 4], $message->dialog->users()->pluck('users.id')->all());

        // Refused before anything reaches the API, so the flood window does not matter.
        $neither = $this->toolOutput($this->callTool('send_message', ['content' => 'To nobody'], $auth), expectError: true);
        $this->assertStringContainsString('exactly one of conversationId', $neither['error']);
    }

    #[Test]
    public function messaging_tools_are_not_offered_to_guests(): void
    {
        $this->prepareDatabase(['group_permission' => [['group_id' => 2, 'permission' => 'mcp.use']]]);

        $tools = $this->toolNames($this->modern('tools/list'));

        $this->assertNotContains('list_conversations', $tools);
        $this->assertNotContains('read_conversation', $tools);
        // Write tools stay listed so the model can ask the user to sign in.
        $this->assertContains('send_message', $tools);
    }
}
