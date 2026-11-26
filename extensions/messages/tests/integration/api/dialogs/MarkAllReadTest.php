<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration\api\dialogs;

use Carbon\Carbon;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Messages\UserDialogState;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Marking every conversation read works as it does for discussions: a
 * watermark on the member, below which nothing counts as unread, rather than
 * a read position moved in every conversation. Positions then only move when
 * a conversation is actually read, which is what read receipts report.
 */
class MarkAllReadTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages');

        // An hour ago, so that replying isn't throttled as flooding.
        $now = Carbon::now()->subHour();

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'alice'],
                ['id' => 4, 'username' => 'bob'],
                ['id' => 5, 'username' => 'carol'],
            ],
            Dialog::class => [
                ['id' => 101, 'type' => 'direct', 'first_message_id' => 101, 'last_message_id' => 102, 'last_message_at' => $now],
                ['id' => 102, 'type' => 'direct', 'first_message_id' => 103, 'last_message_id' => 103, 'last_message_at' => $now],
            ],
            DialogMessage::class => [
                ['id' => 101, 'dialog_id' => 101, 'user_id' => 4, 'content' => 'One', 'number' => 1, 'created_at' => $now],
                ['id' => 102, 'dialog_id' => 101, 'user_id' => 4, 'content' => 'Two', 'number' => 2, 'created_at' => $now],
                ['id' => 103, 'dialog_id' => 102, 'user_id' => 5, 'content' => 'Hey', 'number' => 1, 'created_at' => $now],
            ],
            'dialog_user' => [
                ['dialog_id' => 101, 'user_id' => 3, 'joined_at' => $now, 'last_read_message_id' => 0],
                ['dialog_id' => 101, 'user_id' => 4, 'joined_at' => $now, 'last_read_message_id' => 102],
                ['dialog_id' => 102, 'user_id' => 3, 'joined_at' => $now, 'last_read_message_id' => 0],
                ['dialog_id' => 102, 'user_id' => 5, 'joined_at' => $now, 'last_read_message_id' => 103],
            ],
        ]);
    }

    private function markAllRead(int $as): void
    {
        $response = $this->send($this->request('POST', '/api/dialogs/read', ['authenticatedAs' => $as]));

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
    }

    private function unreadDialogCount(int $as): int
    {
        $response = $this->send($this->request('GET', "/api/users/$as", ['authenticatedAs' => $as]));

        return json_decode((string) $response->getBody(), true)['data']['attributes']['unreadDialogCount'];
    }

    /** @return array<string, int> Each dialog's unread count, by ID. */
    private function unreadCounts(int $as, array $filter = []): array
    {
        $response = $this->send($this->request('GET', '/api/dialogs', ['authenticatedAs' => $as])->withQueryParams(['filter' => $filter]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        return collect(json_decode((string) $response->getBody(), true)['data'])
            ->mapWithKeys(fn (array $dialog) => [$dialog['id'] => $dialog['attributes']['unreadCount']])
            ->sortKeys()
            ->all();
    }

    private function reply(int $dialogId, int $as): void
    {
        $response = $this->send($this->request('POST', '/api/dialog-messages', [
            'authenticatedAs' => $as,
            'json' => ['data' => ['type' => 'dialog-messages', 'attributes' => ['content' => 'Another'],
                'relationships' => ['dialog' => ['data' => ['type' => 'dialogs', 'id' => (string) $dialogId]]]]],
        ]));

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function it_leaves_nothing_unread_without_moving_any_read_position(): void
    {
        $this->assertSame(2, $this->unreadDialogCount(3));

        $this->markAllRead(3);

        $this->assertSame(0, $this->unreadDialogCount(3));
        $this->assertSame(['101' => 0, '102' => 0], $this->unreadCounts(3));
        $this->assertSame([], $this->unreadCounts(3, ['unread' => '1']));
        $this->assertSame([0, 0], UserDialogState::query()->where('user_id', 3)->orderBy('dialog_id')->pluck('last_read_message_id')->all());
    }

    #[Test]
    public function a_message_sent_afterwards_is_unread(): void
    {
        $this->markAllRead(3);

        $this->reply(101, 4);

        $this->assertSame(1, $this->unreadDialogCount(3));
        $this->assertSame(['101' => 1, '102' => 0], $this->unreadCounts(3));
        $this->assertSame(['101' => 1], $this->unreadCounts(3, ['unread' => '1']));
    }

    #[Test]
    public function reading_a_conversation_afterwards_still_moves_its_position(): void
    {
        $this->markAllRead(3);

        $response = $this->send($this->request('PATCH', '/api/dialogs/101', [
            'authenticatedAs' => 3,
            'json' => ['data' => ['type' => 'dialogs', 'id' => '101', 'attributes' => ['lastReadMessageId' => 102]]],
        ]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals(102, UserDialogState::query()->where('dialog_id', 101)->where('user_id', 3)->value('last_read_message_id'));
    }

    #[Test]
    public function the_watermark_only_moves_forward(): void
    {
        $this->database()->table('users')->where('id', 3)->update(['marked_all_dialogs_read_id' => 500]);

        $this->markAllRead(3);

        $this->assertEquals(500, User::query()->whereKey(3)->value('marked_all_dialogs_read_id'));
    }

    #[Test]
    public function it_is_only_the_members_own(): void
    {
        $this->markAllRead(3);

        $this->assertEquals(0, User::query()->whereKey(4)->value('marked_all_dialogs_read_id'));
        $this->assertEquals(102, UserDialogState::query()->where('dialog_id', 101)->where('user_id', 4)->value('last_read_message_id'));
    }

    #[Test]
    public function it_goes_no_further_than_the_members_own_conversations(): void
    {
        // Carol's conversation with bob is beyond anything alice is in: her
        // watermark, which is hers to export, says nothing about it.
        $this->prepareDatabase([
            Dialog::class => [['id' => 103, 'type' => 'direct', 'first_message_id' => 900, 'last_message_id' => 900, 'last_message_at' => Carbon::now()]],
            DialogMessage::class => [['id' => 900, 'dialog_id' => 103, 'user_id' => 5, 'content' => 'Private', 'number' => 1, 'created_at' => Carbon::now()]],
            'dialog_user' => [
                ['dialog_id' => 103, 'user_id' => 4, 'joined_at' => Carbon::now()],
                ['dialog_id' => 103, 'user_id' => 5, 'joined_at' => Carbon::now()],
            ],
        ]);

        $this->markAllRead(3);

        $this->assertEquals(103, User::query()->whereKey(3)->value('marked_all_dialogs_read_id'));
    }
}
