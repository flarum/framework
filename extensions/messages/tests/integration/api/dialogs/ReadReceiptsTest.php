<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration\api\dialogs;

use Carbon\Carbon;
use Flarum\Group\Group;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Messages\ReadReceipts;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Who has read a member's messages, as the open conversation shows it: how
 * many others share receipts, and the latest of the member's own messages
 * each of them has read. It comes with the conversation's messages, and with
 * nothing else.
 */
class ReadReceiptsTest extends TestCase
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
                ['id' => 6, 'username' => 'dave'],
            ],
            Group::class => [
                ['id' => 10, 'name_singular' => 'Helper', 'name_plural' => 'Helpers'],
            ],
            'group_user' => [
                ['user_id' => 5, 'group_id' => 10],
                ['user_id' => 6, 'group_id' => 10],
            ],
            Dialog::class => [
                // Alice and bob.
                ['id' => 101, 'type' => 'direct', 'first_message_id' => 101, 'last_message_id' => 104, 'last_message_at' => $now],
                // Alice, bob, carol and dave, each added by name.
                ['id' => 102, 'type' => 'group', 'first_message_id' => 201, 'last_message_id' => 204, 'last_message_at' => $now],
                // Alice, writing to the Helpers: carol and dave.
                ['id' => 103, 'type' => 'group', 'first_message_id' => 301, 'last_message_id' => 302, 'last_message_at' => $now],
            ],
            DialogMessage::class => [
                ['id' => 101, 'dialog_id' => 101, 'user_id' => 3, 'content' => 'Hi Bob', 'number' => 1, 'created_at' => $now],
                ['id' => 102, 'dialog_id' => 101, 'user_id' => 4, 'content' => 'Hi Alice', 'number' => 2, 'created_at' => $now],
                ['id' => 103, 'dialog_id' => 101, 'user_id' => 3, 'content' => 'How are you?', 'number' => 3, 'created_at' => $now],
                ['id' => 104, 'dialog_id' => 101, 'user_id' => 3, 'content' => 'Still there?', 'number' => 4, 'created_at' => $now],

                ['id' => 201, 'dialog_id' => 102, 'user_id' => 3, 'content' => 'Hi all', 'number' => 1, 'created_at' => $now],
                ['id' => 202, 'dialog_id' => 102, 'user_id' => 5, 'content' => 'Hi', 'number' => 2, 'created_at' => $now],
                ['id' => 203, 'dialog_id' => 102, 'user_id' => 3, 'content' => 'Plans?', 'number' => 3, 'created_at' => $now],
                // A notice by alice: nothing anyone reads.
                ['id' => 204, 'dialog_id' => 102, 'user_id' => 3, 'type' => 'renamed', 'content' => '', 'number' => 4, 'created_at' => $now],

                ['id' => 301, 'dialog_id' => 103, 'user_id' => 3, 'content' => 'Help!', 'number' => 1, 'created_at' => $now],
                ['id' => 302, 'dialog_id' => 103, 'user_id' => 3, 'content' => 'Anyone?', 'number' => 2, 'created_at' => $now],
            ],
            'dialog_user' => [
                ['dialog_id' => 101, 'user_id' => 3, 'joined_at' => $now, 'last_read_message_id' => 104],
                // Read up to their own message: alice's latest before it is 101.
                ['dialog_id' => 101, 'user_id' => 4, 'joined_at' => $now, 'last_read_message_id' => 102],

                ['dialog_id' => 102, 'user_id' => 3, 'joined_at' => $now, 'last_read_message_id' => 204],
                ['dialog_id' => 102, 'user_id' => 4, 'joined_at' => $now, 'last_read_message_id' => 204],
                ['dialog_id' => 102, 'user_id' => 5, 'joined_at' => $now, 'last_read_message_id' => 202],
                ['dialog_id' => 102, 'user_id' => 6, 'joined_at' => $now, 'last_read_message_id' => 0],

                ['dialog_id' => 103, 'user_id' => 3, 'joined_at' => $now, 'last_read_message_id' => 302],
                // Dave has read the first; carol, a group member who never
                // opened it, has no row at all.
                ['dialog_id' => 103, 'user_id' => 6, 'joined_at' => $now, 'last_read_message_id' => 301, 'is_recipient' => false],
            ],
            'dialog_group' => [
                ['dialog_id' => 103, 'group_id' => 10],
            ],
        ]);
    }

    /** The receipts that come with a conversation's messages, as the stream loads them. */
    private function receipts(int $dialogId, int $as, array $query = []): mixed
    {
        $response = $this->send(
            $this->request('GET', '/api/dialog-messages', ['authenticatedAs' => $as])->withQueryParams(['filter' => ['dialog' => (string) $dialogId], ...$query])
        );

        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $dialog = collect($body['included'] ?? [])->first(fn (array $item) => $item['type'] === 'dialogs' && $item['id'] === (string) $dialogId);

        $this->assertNotNull($dialog, 'The messages come with their conversation.');
        $this->assertArrayHasKey('readReceipts', $dialog['attributes']);

        $receipts = $dialog['attributes']['readReceipts'];

        if (is_array($receipts)) {
            usort($receipts['readers'], fn ($a, $b) => $a['userId'] <=> $b['userId']);
        }

        return $receipts;
    }

    private function optOut(int $userId): void
    {
        $this->database()->table('users')->where('id', $userId)->update(['preferences' => json_encode(['discloseReadReceipts' => false])]);
    }

    #[Test]
    public function one_to_one_shows_the_latest_of_your_messages_the_other_has_read(): void
    {
        $this->assertSame(['members' => 1, 'after' => 0, 'readers' => [['userId' => '4', 'messageId' => 101]]], $this->receipts(101, 3));

        // Bob's own message is the latest he has read of alice's; for bob,
        // alice has read his one message.
        $this->assertSame(['members' => 1, 'after' => 0, 'readers' => [['userId' => '3', 'messageId' => 102]]], $this->receipts(101, 4));
    }

    #[Test]
    public function a_group_counts_every_other_member_and_lists_those_who_have_read_any(): void
    {
        $this->assertSame([
            'members' => 3,
            'after' => 0,
            'readers' => [
                ['userId' => '4', 'messageId' => 203],
                ['userId' => '5', 'messageId' => 201],
            ],
        ], $this->receipts(102, 3));
    }

    #[Test]
    public function writing_to_a_group_counts_its_members_whether_or_not_they_have_opened_it(): void
    {
        $this->assertSame(['members' => 2, 'after' => 0, 'readers' => [['userId' => '6', 'messageId' => 301]]], $this->receipts(103, 3));
    }

    #[Test]
    public function a_member_who_does_not_share_receipts_is_neither_counted_nor_listed(): void
    {
        $this->optOut(5);

        $this->assertSame(['members' => 2, 'after' => 0, 'readers' => [['userId' => '4', 'messageId' => 203]]], $this->receipts(102, 3));
    }

    #[Test]
    public function a_member_who_does_not_share_theirs_sees_nobody_elses(): void
    {
        $this->optOut(3);

        $this->assertNull($this->receipts(102, 3));
    }

    #[Test]
    public function a_member_who_left_is_no_longer_counted(): void
    {
        $this->database()->table('dialog_user')->where('dialog_id', 102)->where('user_id', 4)->delete();

        $this->assertSame(['members' => 2, 'after' => 0, 'readers' => [['userId' => '5', 'messageId' => 201]]], $this->receipts(102, 3));
    }

    #[Test]
    public function a_conversation_with_nobody_else_left_has_no_one_to_count(): void
    {
        $this->app();

        User::query()->whereKey(4)->delete();

        $this->assertSame(['members' => 0, 'after' => 0, 'readers' => []], $this->receipts(101, 3));
    }

    #[Test]
    public function marking_everything_read_is_not_a_receipt(): void
    {
        $this->assertEquals(204, $this->send($this->request('POST', '/api/dialogs/read', ['authenticatedAs' => 6]))->getStatusCode());

        $this->assertSame([
            'members' => 3,
            'after' => 0,
            'readers' => [
                ['userId' => '4', 'messageId' => 203],
                ['userId' => '5', 'messageId' => 201],
            ],
        ], $this->receipts(102, 3));
    }

    #[Test]
    public function reading_a_conversation_is(): void
    {
        $response = $this->send($this->request('PATCH', '/api/dialogs/102', [
            'authenticatedAs' => 6,
            'json' => ['data' => ['type' => 'dialogs', 'id' => '102', 'attributes' => ['lastReadMessageId' => 202]]],
        ]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $this->assertSame([
            'members' => 3,
            'after' => 0,
            'readers' => [
                ['userId' => '4', 'messageId' => 203],
                ['userId' => '5', 'messageId' => 201],
                ['userId' => '6', 'messageId' => 201],
            ],
        ], $this->receipts(102, 3));
    }

    #[Test]
    public function replying_is(): void
    {
        $response = $this->send($this->request('POST', '/api/dialog-messages', [
            'authenticatedAs' => 4,
            'json' => ['data' => ['type' => 'dialog-messages', 'attributes' => ['content' => 'Fine, thanks'],
                'relationships' => ['dialog' => ['data' => ['type' => 'dialogs', 'id' => '101']]]]],
        ]));

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());

        $this->assertSame(['members' => 1, 'after' => 0, 'readers' => [['userId' => '4', 'messageId' => 104]]], $this->receipts(101, 3));
    }

    #[Test]
    public function they_come_only_with_the_conversations_own_messages(): void
    {
        $response = $this->send($this->request('GET', '/api/dialogs', ['authenticatedAs' => 3]));
        $this->assertEquals(200, $response->getStatusCode());

        foreach (json_decode((string) $response->getBody(), true)['data'] as $dialog) {
            $this->assertArrayNotHasKey('readReceipts', $dialog['attributes'], 'Not on the list of conversations.');
        }

        $response = $this->send($this->request('GET', '/api/dialogs/101', ['authenticatedAs' => 3]));
        $this->assertArrayNotHasKey('readReceipts', json_decode((string) $response->getBody(), true)['data']['attributes']);

        // Messages from many conversations, as searching gives them.
        $response = $this->send($this->request('GET', '/api/dialog-messages', ['authenticatedAs' => 3]));
        $this->assertEquals(200, $response->getStatusCode());

        foreach (json_decode((string) $response->getBody(), true)['included'] ?? [] as $item) {
            if ($item['type'] === 'dialogs') {
                $this->assertArrayNotHasKey('readReceipts', $item['attributes'], 'Not with messages from all over.');
            }
        }
    }

    #[Test]
    public function they_cost_the_same_queries_however_many_members_there_are(): void
    {
        $this->app();

        $queries = 0;
        $this->database()->listen(function () use (&$queries) {
            $queries++;
        });

        // The first request also makes the queries a request makes once.
        $this->receipts(102, 3);

        $queries = 0;
        $this->receipts(102, 3);
        $before = $queries;

        // Two more members, one of whom has read everything.
        $this->database()->table('users')->insert([
            ['id' => 7, 'username' => 'erin', 'email' => 'erin@machine.local', 'is_email_confirmed' => true, 'password' => '', 'joined_at' => Carbon::now()],
            ['id' => 8, 'username' => 'frank', 'email' => 'frank@machine.local', 'is_email_confirmed' => true, 'password' => '', 'joined_at' => Carbon::now()],
        ]);
        $this->database()->table('dialog_user')->insert([
            ['dialog_id' => 102, 'user_id' => 7, 'joined_at' => Carbon::now(), 'last_read_message_id' => 204],
            ['dialog_id' => 102, 'user_id' => 8, 'joined_at' => Carbon::now(), 'last_read_message_id' => 0],
        ]);

        $queries = 0;
        $receipts = $this->receipts(102, 3);

        $this->assertSame(5, $receipts['members']);
        $this->assertSame($before, $queries, 'Six members cost what four do.');
    }

    #[Test]
    public function the_readers_themselves_can_be_included_for_the_list_of_who_read_a_message(): void
    {
        $response = $this->send($this->request('GET', '/api/dialogs/103', ['authenticatedAs' => 3])->withQueryParams(['include' => 'readers']));
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame([['type' => 'users', 'id' => '6']], $body['data']['relationships']['readers']['data']);
        $this->assertContains('6', array_column(array_filter($body['included'], fn ($item) => $item['type'] === 'users'), 'id'));

        // Not worked out unless asked for.
        $response = $this->send($this->request('GET', '/api/dialogs/103', ['authenticatedAs' => 3]));
        $this->assertArrayNotHasKey('readers', json_decode((string) $response->getBody(), true)['data']['relationships'] ?? []);
    }

    #[Test]
    public function a_member_who_does_not_share_receipts_cannot_include_the_readers(): void
    {
        $this->optOut(3);

        $response = $this->send($this->request('GET', '/api/dialogs/103', ['authenticatedAs' => 3])->withQueryParams(['include' => 'readers']));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame([], json_decode((string) $response->getBody(), true)['data']['relationships']['readers']['data']);
    }

    #[Test]
    public function messages_sent_before_read_receipts_began_have_none(): void
    {
        // Positions moved by marking everything read, before receipts
        // existed, must not show as receipts.
        $this->setting(ReadReceipts::AFTER_SETTING, '201');

        $this->assertSame([
            'members' => 3,
            'after' => 201,
            'readers' => [['userId' => '4', 'messageId' => 203]],
        ], $this->receipts(102, 3));
    }

    #[Test]
    public function a_member_who_left_a_conversation_with_a_group_is_no_longer_counted(): void
    {
        $this->assertEquals(204, $this->send($this->request('POST', '/api/dialogs/103/leave', ['authenticatedAs' => 5]))->getStatusCode());

        $this->assertSame(['members' => 1, 'after' => 0, 'readers' => [['userId' => '6', 'messageId' => 301]]], $this->receipts(103, 3));
    }

    #[Test]
    public function members_only_in_a_hidden_group_are_neither_counted_nor_named_to_those_who_may_not_see_it(): void
    {
        $this->database()->table('groups')->where('id', 10)->update(['is_hidden' => true]);

        $this->assertSame(['members' => 0, 'after' => 0, 'readers' => []], $this->receipts(103, 3));

        $response = $this->send($this->request('GET', '/api/dialogs/103', ['authenticatedAs' => 3])->withQueryParams(['include' => 'readers']));
        $this->assertSame([], json_decode((string) $response->getBody(), true)['data']['relationships']['readers']['data']);
    }

    #[Test]
    public function members_only_in_a_hidden_group_are_counted_for_those_who_may_see_it(): void
    {
        $this->prepareDatabase(['group_permission' => [['group_id' => 3, 'permission' => 'viewHiddenGroups']]]);
        $this->database()->table('groups')->where('id', 10)->update(['is_hidden' => true]);

        $this->assertSame(['members' => 2, 'after' => 0, 'readers' => [['userId' => '6', 'messageId' => 301]]], $this->receipts(103, 3));
    }

    #[Test]
    public function a_member_who_may_not_see_other_users_learns_nothing_about_them(): void
    {
        $this->database()->table('group_permission')->where('permission', 'viewForum')->delete();

        $this->assertSame(['members' => 0, 'after' => 0, 'readers' => []], $this->receipts(102, 3));
    }

    #[Test]
    public function they_come_only_with_the_first_page_or_a_page_around_a_message(): void
    {
        $dialog = function (array $query): ?array {
            $response = $this->send(
                $this->request('GET', '/api/dialog-messages', ['authenticatedAs' => 3])->withQueryParams(['filter' => ['dialog' => '102'], ...$query])
            );
            $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

            return collect(json_decode((string) $response->getBody(), true)['included'] ?? [])->first(fn ($item) => $item['type'] === 'dialogs');
        };

        $this->assertArrayHasKey('readReceipts', $dialog([])['attributes']);
        $this->assertArrayHasKey('readReceipts', $dialog(['page' => ['near' => '3']])['attributes']);
        $this->assertArrayNotHasKey('readReceipts', $dialog(['page' => ['offset' => '2']])['attributes'] ?? [], 'Not again with older pages.');

        $response = $this->send(
            $this->request('GET', '/api/dialog-messages', ['authenticatedAs' => 3])->withQueryParams(['filter' => ['dialog' => '102', 'id' => '201,203']])
        );
        foreach (json_decode((string) $response->getBody(), true)['included'] ?? [] as $item) {
            if ($item['type'] === 'dialogs') {
                $this->assertArrayNotHasKey('readReceipts', $item['attributes'], 'Not with messages refreshed by ID.');
            }
        }
    }

    #[Test]
    public function a_list_of_conversations_in_the_filter_is_not_a_conversation_opening(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/dialog-messages', ['authenticatedAs' => 3])->withQueryParams(['filter' => ['dialog' => ['101', '102']]])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        foreach (json_decode((string) $response->getBody(), true)['included'] ?? [] as $item) {
            if ($item['type'] === 'dialogs') {
                $this->assertArrayNotHasKey('readReceipts', $item['attributes']);
            }
        }
    }

    #[Test]
    public function including_the_readers_costs_the_same_queries_however_many_there_are_for_those_who_may_edit_them(): void
    {
        // Serialising a user for someone who may edit their credentials reads
        // the user's groups.
        $this->prepareDatabase(['group_permission' => [['group_id' => 3, 'permission' => 'user.editCredentials']]]);
        $this->app();

        $queries = 0;
        $this->database()->listen(function () use (&$queries) {
            $queries++;
        });

        $readers = function (): array {
            $response = $this->send($this->request('GET', '/api/dialogs/102', ['authenticatedAs' => 3])->withQueryParams(['include' => 'readers']));
            $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

            return json_decode((string) $response->getBody(), true)['data']['relationships']['readers']['data'];
        };

        $readers();
        $queries = 0;
        $this->assertCount(2, $readers());
        $before = $queries;

        $this->database()->table('dialog_user')->where('dialog_id', 102)->where('user_id', 6)->update(['last_read_message_id' => 204]);

        $queries = 0;
        $this->assertCount(3, $readers());
        $this->assertSame($before, $queries, 'Three readers cost what two do.');
    }

    #[Test]
    public function an_opt_out_counts_however_the_database_spells_it(): void
    {
        // MySQL's JSON column reads it back with a space after the colon.
        $this->database()->table('users')->where('id', 5)->update(['preferences' => '{"discloseReadReceipts": false}']);

        $this->assertSame(['members' => 2, 'after' => 0, 'readers' => [['userId' => '4', 'messageId' => 203]]], $this->receipts(102, 3));
    }

    #[Test]
    public function members_are_not_counted_for_someone_with_nothing_that_has_receipts(): void
    {
        // Bob hasn't written in the group: no ticks to show him.
        $this->assertSame(['members' => null, 'after' => 0, 'readers' => []], $this->receipts(102, 4));
    }
}
