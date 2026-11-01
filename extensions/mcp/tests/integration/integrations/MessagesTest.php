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
    public function a_conversation_too_long_for_one_page_is_read_in_full_by_paging_older(): void
    {
        $count = 40;
        $this->longConversation($count);

        $auth = $this->bearer($this->developerToken(2));
        $pages = [];
        $arguments = ['id' => '2', 'limit' => $count];

        for ($i = 0; $i < 10; $i++) {
            $read = $this->toolOutput($this->callTool('read_conversation', $arguments, $auth));
            $pages[] = array_column($read['messages'], 'number');

            if (! $read['paging']['older']) {
                break;
            }

            $arguments = ['id' => '2', 'limit' => $count, ...$read['paging']['older']];
        }

        // The first page is cut, and keeps the latest messages.
        $this->assertGreaterThan(1, count($pages));
        $this->assertSame($count, $pages[0][count($pages[0]) - 1]);

        $this->assertSame(range(1, $count), array_merge(...array_reverse($pages)));
    }

    #[Test]
    public function walking_newer_over_pages_cut_to_size_visits_every_message_once(): void
    {
        $count = 60;
        $this->longConversation($count);

        $auth = $this->bearer($this->developerToken(2));

        // Start at the oldest page, reached by paging older.
        $older = $this->walkConversation($auth, ['limit' => 50], 'older');
        $this->assertGreaterThan(2, count($older['pages']), 'pages are cut to size');
        $this->assertSame(range(1, $count), array_merge(...array_reverse($older['pages'])));

        $newer = $this->walkConversation($auth, $older['last'], 'newer');
        $this->assertSame(range(1, $count), array_merge(...$newer['pages']));
    }

    /**
     * Messages of about 3,900 characters each in conversation 2: 25 of them
     * exceed the payload cap.
     */
    protected function longConversation(int $count): void
    {
        $messages = [];
        for ($number = 1; $number <= $count; $number++) {
            $messages[] = [
                'id' => 100 + $number,
                'dialog_id' => 2,
                'user_id' => $number % 2 ? 2 : 3,
                'content' => '<t><p>'.$number.' '.str_repeat('lorem ', 650).'</p></t>',
                'number' => $number,
                'created_at' => Carbon::now()->subMinutes(100 - $number),
            ];
        }

        $this->prepareDatabase([
            Dialog::class => [
                ['id' => 2, 'type' => 'direct', 'first_message_id' => 101, 'last_message_id' => 100 + $count, 'last_message_at' => Carbon::now()],
            ],
            DialogMessage::class => $messages,
            'dialog_user' => [
                ['dialog_id' => 2, 'user_id' => 2, 'joined_at' => Carbon::now(), 'last_read_message_id' => 100 + $count],
                ['dialog_id' => 2, 'user_id' => 3, 'joined_at' => Carbon::now(), 'last_read_message_id' => 100 + $count],
            ],
        ]);
    }

    /**
     * Follows one paging hint of conversation 2 until there is none.
     *
     * @param array<string, string> $auth
     * @param array<string, mixed> $arguments
     * @return array{pages: list<list<int>>, last: array<string, mixed>} the pages' message numbers in the order visited, and the arguments of the last page
     */
    protected function walkConversation(array $auth, array $arguments, string $direction): array
    {
        $pages = [];
        $limit = $arguments['limit'];

        for ($i = 0; $i < 20; $i++) {
            $read = $this->toolOutput($this->callTool('read_conversation', ['id' => '2', ...$arguments], $auth));
            $pages[] = array_column($read['messages'], 'number');

            $hint = $read['paging'][$direction] ?? null;
            if (! $hint) {
                break;
            }

            $arguments = ['limit' => $limit, ...$hint];
        }

        return ['pages' => $pages, 'last' => $arguments];
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
    public function mark_conversations_read_marks_given_or_all_conversations(): void
    {
        $this->prepareDatabase([
            DialogMessage::class => [
                ['id' => 3, 'dialog_id' => 1, 'user_id' => 3, 'content' => '<t><p>Still there?</p></t>', 'number' => 3, 'created_at' => Carbon::now()],
            ],
        ]);

        $auth = $this->bearer($this->developerToken(2));
        $this->database()->table('dialogs')->where('id', 1)->update(['last_message_id' => 3]);

        $list = $this->toolOutput($this->callTool('list_conversations', [], $auth));
        $this->assertSame(1, $list['data'][0]['unreadCount']);

        // Reading leaves it unread.
        $this->callTool('read_conversation', ['id' => '1'], $auth);
        $this->assertSame(2, $this->lastRead(1, 2));

        $marked = $this->toolOutput($this->callTool('mark_conversations_read', ['ids' => ['1', '999']], $auth));
        $this->assertSame(['1'], $marked['marked']);
        $this->assertSame('999', $marked['failed'][0]['id']);
        $this->assertSame(3, $this->lastRead(1, 2));

        $this->database()->table('dialog_user')->where(['dialog_id' => 1, 'user_id' => 2])->update(['last_read_message_id' => 1]);

        $this->assertSame(['marked' => 'all'], $this->toolOutput($this->callTool('mark_conversations_read', [], $auth)));
        $this->assertSame(3, $this->lastRead(1, 2));
    }

    #[Test]
    public function delete_message_is_refused_unless_the_forum_allows_it(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $this->toolOutput($this->callTool('delete_message', ['messageId' => '2'], $auth), expectError: true);
        $this->assertTrue(DialogMessage::query()->whereKey(2)->exists());
    }

    #[Test]
    public function delete_message_deletes_own_messages_when_the_forum_allows_it(): void
    {
        $this->setting('flarum-messages.allow_delete_own_messages', '-1');

        $auth = $this->bearer($this->developerToken(2));

        $this->assertSame(['deleted' => true, 'messageId' => '2'], $this->toolOutput($this->callTool('delete_message', ['messageId' => '2'], $auth)));
        $this->assertFalse(DialogMessage::query()->whereKey(2)->exists());

        // Someone else's message stays.
        $this->toolOutput($this->callTool('delete_message', ['messageId' => '1'], $auth), expectError: true);
        $this->assertTrue(DialogMessage::query()->whereKey(1)->exists());
    }

    #[Test]
    public function leave_conversation_leaves_a_group_but_not_a_one_to_one_conversation(): void
    {
        $this->prepareGroup();

        $auth = $this->bearer($this->developerToken(2));

        $this->toolOutput($this->callTool('leave_conversation', ['conversationId' => '1'], $auth), expectError: true);
        $this->assertNotNull($this->lastRead(1, 2));

        $this->assertSame(['left' => true, 'conversationId' => '2'], $this->toolOutput($this->callTool('leave_conversation', ['conversationId' => '2'], $auth)));
        $this->assertNull($this->lastRead(2, 2));
        $this->assertEqualsCanonicalizing([3, 4], Dialog::query()->findOrFail(2)->users()->pluck('users.id')->all());
    }

    #[Test]
    public function rename_conversation_names_a_group_where_the_forum_allows_it(): void
    {
        $this->setting('flarum-messages.allow_group_names', '1');
        $this->prepareGroup();

        $auth = $this->bearer($this->developerToken(2));

        // A one-to-one conversation has no name.
        $this->toolOutput($this->callTool('rename_conversation', ['conversationId' => '1', 'name' => 'Us'], $auth), expectError: true);
        $this->assertNull(Dialog::query()->findOrFail(1)->name);

        $renamed = $this->toolOutput($this->callTool('rename_conversation', ['conversationId' => '2', 'name' => 'Trip planning'], $auth));
        $this->assertSame(['conversationId' => '2', 'name' => 'Trip planning', 'title' => 'Trip planning'], $renamed);
        $this->assertSame('Trip planning', Dialog::query()->findOrFail(2)->name);
    }

    #[Test]
    public function rename_conversation_removes_a_name_given_an_empty_one(): void
    {
        $this->setting('flarum-messages.allow_group_names', '1');
        $this->prepareGroup(name: 'Trip planning');

        $cleared = $this->toolOutput($this->callTool('rename_conversation', ['conversationId' => '2', 'name' => ''], $this->bearer($this->developerToken(2))));

        $this->assertNull($cleared['name']);
        $this->assertNull(Dialog::query()->findOrFail(2)->name);
    }

    #[Test]
    public function rename_conversation_is_refused_where_the_forum_does_not_allow_names(): void
    {
        $this->prepareGroup();

        $this->toolOutput($this->callTool('rename_conversation', ['conversationId' => '2', 'name' => 'Trip planning'], $this->bearer($this->developerToken(2))), expectError: true);
        $this->assertNull(Dialog::query()->findOrFail(2)->name);
    }

    /**
     * Group conversation 2, between normal, bob and carol.
     */
    protected function prepareGroup(?string $name = null): void
    {
        $this->prepareDatabase([
            Dialog::class => [
                ['id' => 2, 'type' => 'group', 'name' => $name, 'first_message_id' => 4, 'last_message_id' => 4, 'last_message_at' => Carbon::now()],
            ],
            DialogMessage::class => [
                ['id' => 4, 'dialog_id' => 2, 'user_id' => 3, 'content' => '<t><p>Hi all</p></t>', 'number' => 1, 'created_at' => Carbon::now()],
            ],
            'dialog_user' => [
                ['dialog_id' => 2, 'user_id' => 2, 'joined_at' => Carbon::now(), 'last_read_message_id' => 4],
                ['dialog_id' => 2, 'user_id' => 3, 'joined_at' => Carbon::now(), 'last_read_message_id' => 4],
                ['dialog_id' => 2, 'user_id' => 4, 'joined_at' => Carbon::now(), 'last_read_message_id' => 4],
            ],
        ]);
    }

    protected function lastRead(int $dialogId, int $userId): ?int
    {
        $value = $this->database()->table('dialog_user')->where(['dialog_id' => $dialogId, 'user_id' => $userId])->value('last_read_message_id');

        return $value === null ? null : (int) $value;
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
