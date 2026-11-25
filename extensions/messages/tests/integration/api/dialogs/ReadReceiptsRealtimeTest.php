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
use Flarum\Messages\Dialog\Event\UserRead;
use Flarum\Messages\DialogMessage;
use Flarum\Messages\ReadReceipts;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Pusher\Pusher;
use stdClass;

/**
 * With flarum/realtime, reading further tells the authors of what was just
 * read, and only them: the latest of their messages the reader now has.
 */
class ReadReceiptsRealtimeTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /** @var array<int, array{0: string|string[], 1: string, 2: mixed}> */
    private array $pushed = [];

    protected function setUp(): void
    {
        parent::setUp();

        $now = Carbon::now()->subHour();

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'alice'],
                ['id' => 4, 'username' => 'bob'],
                ['id' => 5, 'username' => 'carol'],
            ],
            Dialog::class => [
                ['id' => 102, 'type' => 'group', 'first_message_id' => 201, 'last_message_id' => 204, 'last_message_at' => $now],
            ],
            DialogMessage::class => [
                ['id' => 201, 'dialog_id' => 102, 'user_id' => 3, 'content' => 'Hi all', 'number' => 1, 'created_at' => $now],
                ['id' => 202, 'dialog_id' => 102, 'user_id' => 5, 'content' => 'Hi', 'number' => 2, 'created_at' => $now],
                ['id' => 203, 'dialog_id' => 102, 'user_id' => 3, 'content' => 'Plans?', 'number' => 3, 'created_at' => $now],
                ['id' => 204, 'dialog_id' => 102, 'user_id' => 4, 'content' => 'None', 'number' => 4, 'created_at' => $now],
            ],
            'dialog_user' => [
                ['dialog_id' => 102, 'user_id' => 3, 'joined_at' => $now, 'last_read_message_id' => 203],
                ['dialog_id' => 102, 'user_id' => 4, 'joined_at' => $now, 'last_read_message_id' => 204],
                ['dialog_id' => 102, 'user_id' => 5, 'joined_at' => $now, 'last_read_message_id' => 202],
            ],
        ]);
    }

    private function connect(): void
    {
        $this->extension('flarum-messages', 'flarum-realtime');

        $pusher = $this->createStub(Pusher::class);
        // Everyone is connected.
        $pusher->method('getChannels')->willReturn((object) ['channels' => (object) [
            'private-user=3' => new stdClass(),
            'private-user=4' => new stdClass(),
            'private-user=5' => new stdClass(),
        ]]);
        $pusher->method('trigger')->willReturnCallback(function ($channels, $event, $data) {
            $this->pushed[] = [$channels, $event, $data];

            return new stdClass();
        });
        $this->app()->getContainer()->instance(Pusher::class, $pusher);
    }

    private function read(int $as, int $messageId): void
    {
        $response = $this->send($this->request('PATCH', '/api/dialogs/102', [
            'authenticatedAs' => $as,
            'json' => ['data' => ['type' => 'dialogs', 'id' => '102', 'attributes' => ['lastReadMessageId' => $messageId]]],
        ]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    /** @return array<string, mixed> Each read signal's payload, by channel. */
    private function signals(): array
    {
        $signals = [];

        foreach ($this->pushed as [$channels, $event, $data]) {
            if ($event === UserRead::class) {
                foreach ((array) $channels as $channel) {
                    $signals[$channel] = $data;
                }
            }
        }

        ksort($signals);

        return $signals;
    }

    private function optOut(int $userId): void
    {
        $this->database()->table('users')->where('id', $userId)->update(['preferences' => json_encode(['discloseReadReceipts' => false])]);
    }

    #[Test]
    public function the_authors_of_what_was_read_are_told_how_far(): void
    {
        $this->connect();

        // Carol reads past alice's 203 and bob's 204.
        $this->read(5, 204);

        $this->assertSame([
            'private-user=3' => ['dialogId' => '102', 'userId' => '5', 'messageId' => 203],
            'private-user=4' => ['dialogId' => '102', 'userId' => '5', 'messageId' => 204],
        ], $this->signals());
    }

    #[Test]
    public function reading_only_your_own_messages_tells_nobody(): void
    {
        $this->connect();

        $this->database()->table('dialog_user')->where('dialog_id', 102)->where('user_id', 3)->update(['last_read_message_id' => 202]);

        $this->read(3, 203);

        $this->assertSame([], $this->signals());
    }

    #[Test]
    public function a_reader_who_does_not_share_receipts_tells_nobody(): void
    {
        $this->connect();
        $this->optOut(5);

        $this->read(5, 204);

        $this->assertSame([], $this->signals());
    }

    #[Test]
    public function an_author_who_does_not_share_receipts_is_not_told(): void
    {
        $this->connect();
        $this->optOut(4);

        $this->read(5, 204);

        $this->assertSame(['private-user=3'], array_keys($this->signals()));
    }

    #[Test]
    public function an_author_who_left_is_not_told(): void
    {
        $this->connect();
        $this->database()->table('dialog_user')->where('dialog_id', 102)->where('user_id', 4)->delete();

        $this->read(5, 204);

        $this->assertSame(['private-user=3'], array_keys($this->signals()));
    }

    #[Test]
    public function marking_everything_read_tells_nobody(): void
    {
        $this->connect();

        $this->assertEquals(204, $this->send($this->request('POST', '/api/dialogs/read', ['authenticatedAs' => 5]))->getStatusCode());

        $this->assertSame([], $this->signals());
    }

    #[Test]
    public function only_the_authors_of_the_latest_comments_read_at_once_are_told(): void
    {
        $this->extension('flarum-messages');

        $now = Carbon::now();
        $messages = [];

        // Alice's 201 is followed by sixty of bob's.
        foreach (range(1, ReadReceipts::MAX_SIGNALLED + 10) as $i) {
            $messages[] = ['id' => 300 + $i, 'dialog_id' => 102, 'user_id' => 4, 'content' => "Bob $i", 'number' => 10 + $i, 'created_at' => $now];
        }

        $this->prepareDatabase([DialogMessage::class => $messages]);

        $this->app();

        $state = \Flarum\Messages\UserDialogState::query()->where('dialog_id', 102)->where('user_id', 5)->firstOrFail();
        $state->last_read_message_id = 300 + ReadReceipts::MAX_SIGNALLED + 10;

        $signals = $this->app()->getContainer()->make(ReadReceipts::class)->signals(new UserRead($state, 200));

        // Bob is told; alice, whose message is further back, sees it next time
        // she loads the conversation.
        $this->assertSame([4], array_keys($signals));
        $this->assertSame(300 + ReadReceipts::MAX_SIGNALLED + 10, $signals[4]['messageId']);
    }

    #[Test]
    public function messages_sent_before_read_receipts_began_tell_nobody(): void
    {
        $this->setting(ReadReceipts::AFTER_SETTING, '203');
        $this->connect();

        $this->read(5, 204);

        // Alice's 203 is from before; bob's 204 is not.
        $this->assertSame(['private-user=4'], array_keys($this->signals()));
    }

    #[Test]
    public function an_author_who_may_not_see_users_is_not_told(): void
    {
        $this->connect();
        // Nobody keeps viewForum but admins.
        $this->database()->table('group_permission')->where('permission', 'viewForum')->delete();

        $this->read(5, 204);

        $this->assertSame([], $this->signals());
    }

    #[Test]
    public function an_author_who_may_not_see_a_hidden_group_is_not_told_who_read_through_it(): void
    {
        // Dave is in the conversation only through a hidden Group.
        $this->prepareDatabase([
            User::class => [['id' => 6, 'username' => 'dave']],
            \Flarum\Group\Group::class => [['id' => 10, 'name_singular' => 'Secret', 'name_plural' => 'Secrets', 'is_hidden' => true]],
            'group_user' => [['user_id' => 6, 'group_id' => 10]],
            'dialog_group' => [['dialog_id' => 102, 'group_id' => 10]],
        ]);

        $this->connect();
        $this->read(6, 204);

        $this->assertSame([], $this->signals());
    }

    #[Test]
    public function authors_are_told_on_a_forum_only_members_may_see(): void
    {
        // Guests may not see the forum; members may.
        $this->prepareDatabase(['group_permission' => [['group_id' => 3, 'permission' => 'viewForum']]]);
        $this->connect();
        $this->database()->table('group_permission')->where('group_id', 2)->where('permission', 'viewForum')->delete();

        $this->read(5, 204);

        $this->assertSame(['private-user=3', 'private-user=4'], array_keys($this->signals()));
    }

    #[Test]
    public function a_reader_who_left_before_the_broadcast_was_sent_tells_nobody(): void
    {
        // Authors who may see hidden Groups skip that check of the reader.
        $this->extension('flarum-messages');
        $this->prepareDatabase(['group_permission' => [['group_id' => 3, 'permission' => 'viewHiddenGroups']]]);
        $this->app();
        $state = \Flarum\Messages\UserDialogState::query()->where('dialog_id', 102)->where('user_id', 5)->firstOrFail();
        $state->last_read_message_id = 204;
        $event = new UserRead($state, 202);

        $this->database()->table('dialog_user')->where('dialog_id', 102)->where('user_id', 5)->update(['left_at' => Carbon::now()]);

        $this->assertSame([], $this->app()->getContainer()->make(ReadReceipts::class)->signals($event));
    }

    #[Test]
    public function a_queued_read_keeps_the_range_it_was_raised_for(): void
    {
        $this->extension('flarum-messages');
        $this->app();
        $state = \Flarum\Messages\UserDialogState::query()->where('dialog_id', 102)->where('user_id', 5)->firstOrFail();
        $state->last_read_message_id = 203;
        $event = new UserRead($state, 202);

        // Read further before a worker got to it.
        $state->last_read_message_id = 204;

        $this->assertSame([3], array_keys($this->app()->getContainer()->make(ReadReceipts::class)->signals($event)));
    }
}
