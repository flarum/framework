<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration\api\dialog_messages;

use Carbon\Carbon;
use Flarum\Api\Client;
use Flarum\Foundation\Config;
use Flarum\Group\Group;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Messages\Job\SendMessageNotificationsJob;
use Flarum\Messages\Tests\integration\CapturesSentMail;
use Flarum\Notification\Job\SendEmailNotificationJob;
use Flarum\Notification\NotificationMailer;
use Flarum\Notification\NotificationSyncer;
use Flarum\Realtime\Push\Jobs\SendDialogMessageJob;
use Flarum\Realtime\Push\Payload\Generator as PayloadGenerator;
use Flarum\Realtime\Push\RealtimeRegistry;
use Flarum\Realtime\Websocket\Channel\Manager as ChannelManager;
use Flarum\Realtime\Websocket\Message\Message as WebsocketMessage;
use Flarum\Realtime\Websocket\Settings as WebsocketSettings;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Queue\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Pusher\Pusher;
use Ratchet\ConnectionInterface;
use stdClass;

class GroupAddressTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use CapturesSentMail;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension('flarum-messages');
        $this->setting('flarum-messages.recipient_groups', '[4]');
        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'alice', 'email' => 'alice@machine.local', 'is_email_confirmed' => 1, 'preferences' => '{"notify_messageReceived_email":false}'],
            ],
            'group_user' => [['user_id' => 3, 'group_id' => 4]],
        ]);
    }

    protected function start(int $actor = 1, array $groups = [4], array $users = []): ResponseInterface
    {
        return $this->send($this->request('POST', '/api/dialog-messages', [
            'authenticatedAs' => $actor,
            'json' => ['data' => ['type' => 'dialog-messages', 'attributes' => [
                'content' => 'Hello team',
                'groups' => array_map(fn ($id) => ['id' => $id], $groups),
                'users' => array_map(fn ($id) => ['id' => $id], $users),
            ]]],
        ])->withQueryParams(['include' => 'dialog']));
    }

    protected function dialogId(ResponseInterface $response): string
    {
        $body = (string) $response->getBody();
        $this->assertSame(201, $response->getStatusCode(), $body);

        return json_decode($body, true)['data']['relationships']['dialog']['data']['id'];
    }

    #[Test]
    public function an_admin_can_address_a_group_and_its_member_can_read_the_conversation(): void
    {
        $response = $this->start();
        $id = $this->dialogId($response);
        $dialog = collect(json_decode((string) $response->getBody(), true)['included'])->firstWhere('type', 'dialogs');
        $this->assertSame('group', $dialog['attributes']['type']);
        $this->assertSame(200, $this->send($this->request('GET', "/api/dialogs/$id", ['authenticatedAs' => 3]))->getStatusCode());
    }

    #[Test]
    public function a_group_member_has_unread_messages_before_opening_and_can_mark_them_read(): void
    {
        $id = $this->dialogId($this->start());
        $response = $this->send($this->request('GET', '/api/users/3', ['authenticatedAs' => 3]));
        $this->assertSame(1, json_decode((string) $response->getBody(), true)['data']['attributes']['unreadDialogCount']);
        $response = $this->send($this->request('GET', "/api/dialogs/$id", ['authenticatedAs' => 3]));
        $dialog = json_decode((string) $response->getBody(), true)['data'];
        $this->assertSame(1, $dialog['attributes']['unreadCount']);
        $response = $this->send($this->request('PATCH', "/api/dialogs/$id", [
            'authenticatedAs' => 3,
            'json' => ['data' => ['type' => 'dialogs', 'id' => $id, 'attributes' => ['lastReadMessageId' => $dialog['attributes']['lastMessageId']]]],
        ]));
        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $response = $this->send($this->request('GET', '/api/users/3', ['authenticatedAs' => 3]));
        $this->assertSame(0, json_decode((string) $response->getBody(), true)['data']['attributes']['unreadDialogCount']);
    }

    #[Test]
    public function the_sender_can_continue_a_conversation_addressed_only_to_a_group(): void
    {
        $id = $this->dialogId($this->start());
        $response = $this->reply($id, 1);
        $this->assertSame(201, $response->getStatusCode(), (string) $response->getBody());
    }

    protected function reply(string $id, int $actor): ResponseInterface
    {
        return $this->send($this->request('POST', '/api/dialog-messages', [
            'authenticatedAs' => $actor,
            'json' => ['data' => ['type' => 'dialog-messages', 'attributes' => ['content' => 'Reply'],
                'relationships' => ['dialog' => ['data' => ['type' => 'dialogs', 'id' => $id]]]]],
        ]));
    }

    #[Test]
    public function marking_all_read_covers_group_conversations_without_creating_individual_access(): void
    {
        $id = $this->dialogId($this->start());
        $response = $this->send($this->request('POST', '/api/dialogs/read', ['authenticatedAs' => 3]));
        $this->assertSame(204, $response->getStatusCode());
        $response = $this->send($this->request('GET', '/api/users/3', ['authenticatedAs' => 3]));
        $this->assertSame(0, json_decode((string) $response->getBody(), true)['data']['attributes']['unreadDialogCount']);
        $this->database()->table('group_user')->where('user_id', 3)->delete();
        $this->assertSame(404, $this->send($this->request('GET', "/api/dialogs/$id", ['authenticatedAs' => 3]))->getStatusCode());
    }

    #[Test]
    public function leaving_a_group_addressed_conversation_is_persistent_even_after_rejoining_the_group(): void
    {
        $id = $this->dialogId($this->start());
        $response = $this->send($this->request('POST', "/api/dialogs/$id/leave", ['authenticatedAs' => 3]));
        $this->assertSame(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(404, $this->send($this->request('GET', "/api/dialogs/$id", ['authenticatedAs' => 3]))->getStatusCode());
        $this->database()->table('group_user')->where('user_id', 3)->delete();
        $this->database()->table('group_user')->insert(['user_id' => 3, 'group_id' => 4]);
        $this->assertSame(404, $this->send($this->request('GET', "/api/dialogs/$id", ['authenticatedAs' => 3]))->getStatusCode());
        $this->assertSame(200, $this->send($this->request('GET', "/api/dialogs/$id", ['authenticatedAs' => 1]))->getStatusCode());
    }

    #[Test]
    public function group_members_receive_one_email_until_they_read_the_conversation(): void
    {
        $this->captureSentMail();
        $alice = User::findOrFail(3);
        $alice->preferences = [];
        $alice->save();
        $id = $this->dialogId($this->start());
        $this->assertSame(1, $this->emailsTo('alice@machine.local'));
        $this->assertSame(201, $this->reply($id, 1)->getStatusCode());
        $this->assertSame(1, $this->emailsTo('alice@machine.local'));
        $this->send($this->request('POST', '/api/dialogs/read', ['authenticatedAs' => 3]));
        $this->assertSame(201, $this->reply($id, 1)->getStatusCode());
        $this->assertSame(2, $this->emailsTo('alice@machine.local'));
        $this->database()->table('group_user')->where('user_id', 3)->delete();
        $this->reply($id, 1);
        $this->assertSame(2, $this->emailsTo('alice@machine.local'));
    }

    #[Test]
    public function unread_filter_includes_group_only_members(): void
    {
        $id = $this->dialogId($this->start());
        $response = $this->send($this->request('GET', '/api/dialogs', ['authenticatedAs' => 3])->withQueryParams(['filter' => ['unread' => '1']]));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([$id], array_column(json_decode((string) $response->getBody(), true)['data'], 'id'));
    }

    #[Test]
    public function only_authorized_senders_can_select_groups_and_the_api_reports_that_capability(): void
    {
        $this->assertSame(403, $this->start(actor: 3)->getStatusCode());
        $response = $this->send($this->request('GET', '/api/groups/4', ['authenticatedAs' => 1]));
        $this->assertTrue(json_decode((string) $response->getBody(), true)['data']['attributes']['canMessage'] ?? false);
        $response = $this->send($this->request('GET', '/api/groups/4', ['authenticatedAs' => 3]));
        $this->assertFalse(json_decode((string) $response->getBody(), true)['data']['attributes']['canMessage'] ?? true);
    }

    #[Test]
    public function malformed_group_ids_are_rejected_without_creating_a_conversation(): void
    {
        $response = $this->start(groups: ['4oops']);
        $this->assertSame(422, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(0, Dialog::query()->count());
    }

    #[Test]
    public function malformed_group_ids_from_a_throttled_sender_are_validation_errors(): void
    {
        $this->prepareDatabase(['group_permission' => [['group_id' => 3, 'permission' => 'dialog.messageGroups']]]);
        set_error_handler(function (int $severity, string $message, string $file, int $line): never {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        }, E_WARNING);

        try {
            $response = $this->start(actor: 3, groups: [['invalid']]);
        } finally {
            restore_error_handler();
        }
        $this->assertSame(422, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function addressed_groups_are_named_and_returned_without_listing_all_their_members(): void
    {
        $id = $this->dialogId($this->start());
        $response = $this->send($this->request('GET', "/api/dialogs/$id", ['authenticatedAs' => 1])->withQueryParams(['include' => 'groups,users']));
        $body = (string) $response->getBody();
        $this->assertSame(200, $response->getStatusCode(), $body);
        $data = json_decode($body, true)['data'];
        $this->assertStringContainsString('Mods', $data['attributes']['title']);
        $this->assertSame([['type' => 'groups', 'id' => '4']], $data['relationships']['groups']['data']);
        $this->assertSame([['type' => 'users', 'id' => '1']], $data['relationships']['users']['data']);
    }

    #[Test]
    public function conversation_titles_translate_default_group_names(): void
    {
        $id = $this->dialogId($this->start());
        $this->app()->getContainer()->make(\Flarum\Locale\Translator::class)
            ->getCatalogue('en')->set('core.group.mods', 'Moderatoren', 'messages+intl-icu');
        $group = $this->send($this->request('GET', '/api/groups/4', ['authenticatedAs' => 1]));
        $this->assertSame('Moderatoren', json_decode((string) $group->getBody(), true)['data']['attributes']['namePlural']);
        $response = $this->send($this->request('GET', "/api/dialogs/$id", ['authenticatedAs' => 1]));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Moderatoren', json_decode((string) $response->getBody(), true)['data']['attributes']['title']);
    }

    #[Test]
    public function realtime_reaches_group_members_and_rechecks_access_when_delivering_queued_payloads(): void
    {
        $id = $this->dialogId($this->start());
        $message = DialogMessage::where('dialog_id', $id)->firstOrFail();
        $pusher = $this->createMock(Pusher::class);
        $pusher->method('getChannels')->willReturn((object) ['channels' => (object) ['private-user=1' => new stdClass(), 'private-user=3' => new stdClass()]]);
        $channels = [];
        $pusher->method('trigger')->willReturnCallback(function ($channel) use (&$channels) {
            $channels[] = $channel;

            return new stdClass();
        });
        $this->app()->getContainer()->instance(Pusher::class, $pusher);
        $registry = new RealtimeRegistry();
        $registry->addModelEndpoint(DialogMessage::class, 'dialog-messages');
        $generator = new PayloadGenerator($this->app()->getContainer()->make(Client::class), $registry);
        $jobs = [];
        $queue = $this->createMock(Queue::class);
        $queue->method('push')->willReturnCallback(function ($job) use (&$jobs) { $jobs[] = $job; });
        (new SendDialogMessageJob('created', $message))($queue);
        foreach ($jobs as $job) {
            $job->handle($generator, $pusher);
        }
        $this->assertEqualsCanonicalizing(['private-user=1', 'private-user=3'], $channels);
        $channels = [];
        $this->database()->table('group_user')->where('user_id', 3)->delete();
        foreach ($jobs as $job) {
            $job->handle($generator, $pusher);
        }
        $this->assertSame(['private-user=1'], $channels);
    }

    public static function groupTargetForms(): array
    {
        return ['one Group' => [[4]], 'duplicate Groups' => [array_fill(0, 11, 4)]];
    }

    #[Test]
    #[DataProvider('groupTargetForms')]
    public function group_targets_cannot_bypass_the_hourly_limit_on_people_reached(array $groups): void
    {
        $users = $members = [];
        foreach (range(10, 30) as $id) {
            $users[] = ['id' => $id, 'username' => "member$id", 'email' => "member$id@machine.local", 'is_email_confirmed' => 1, 'preferences' => '{"notify_messageReceived_email":false}'];
            $members[] = ['user_id' => $id, 'group_id' => 4];
        }
        $this->prepareDatabase([
            User::class => $users, 'group_user' => $members,
            'group_permission' => [['group_id' => 3, 'permission' => 'dialog.messageGroups']],
        ]);
        $response = $this->start(actor: 3, groups: $groups);
        $this->assertSame(429, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(0, Dialog::query()->count());
    }

    #[Test]
    public function at_most_ten_groups_can_be_addressed_at_once(): void
    {
        $ids = range(5, 15);
        $this->setting('flarum-messages.recipient_groups', json_encode($ids));
        $this->prepareDatabase([Group::class => array_map(fn ($id) => ['id' => $id, 'name_singular' => "Team$id", 'name_plural' => "Team$id"], $ids)]);
        $response = $this->start(groups: $ids);
        $this->assertSame(422, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(0, Dialog::query()->count());
    }

    #[Test]
    public function ten_empty_groups_can_be_addressed_without_expanding_them_into_users(): void
    {
        $ids = range(5, 14);
        $this->setting('flarum-messages.recipient_groups', json_encode($ids));
        $this->prepareDatabase([Group::class => array_map(fn ($id) => ['id' => $id, 'name_singular' => "Team$id", 'name_plural' => "Team$id"], $ids)]);
        $id = $this->dialogId($this->start(groups: $ids));
        $this->assertSame(10, Dialog::findOrFail($id)->groups()->count());
        $this->assertSame(1, Dialog::findOrFail($id)->users()->count());
    }

    #[Test]
    public function hourly_reach_is_recorded_before_the_notification_queue_runs(): void
    {
        $users = $members = [];
        foreach (range(10, 20) as $id) {
            $users[] = ['id' => $id, 'username' => "member$id", 'email' => "member$id@machine.local", 'is_email_confirmed' => 1];
            $members[] = ['user_id' => $id, 'group_id' => 4];
        }
        $this->prepareDatabase([User::class => $users, 'group_user' => $members,
            'group_permission' => [['group_id' => 3, 'permission' => 'dialog.messageGroups']]]);
        $this->app()->getContainer()->instance(Queue::class, $this->createStub(Queue::class));
        $this->dialogId($this->start(actor: 3));
        Carbon::setTestNow(Carbon::now()->addSeconds(11));

        try {
            $response = $this->start(actor: 3);
            $this->assertSame(429, $response->getStatusCode(), (string) $response->getBody());
        } finally {
            Carbon::setTestNow();
        }
    }

    #[Test]
    public function membership_changes_control_full_history_and_an_individual_recipient_keeps_access(): void
    {
        $this->prepareDatabase([User::class => [['id' => 4, 'username' => 'bob', 'email' => 'bob@machine.local', 'is_email_confirmed' => 1, 'preferences' => '{"notify_messageReceived_email":false}']]]);
        $id = $this->dialogId($this->start(users: [3]));
        $this->reply($id, 1);
        $this->assertSame(404, $this->send($this->request('GET', "/api/dialogs/$id", ['authenticatedAs' => 4]))->getStatusCode());
        $this->database()->table('group_user')->insert(['user_id' => 4, 'group_id' => 4]);
        $response = $this->send($this->request('GET', '/api/dialog-messages', ['authenticatedAs' => 4])->withQueryParams(['filter' => ['dialog' => $id]]));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(2, json_decode((string) $response->getBody(), true)['data']);
        $this->database()->table('group_user')->whereIn('user_id', [3, 4])->delete();
        $this->assertSame(404, $this->send($this->request('GET', "/api/dialogs/$id", ['authenticatedAs' => 4]))->getStatusCode());
        $this->assertSame(200, $this->send($this->request('GET', "/api/dialogs/$id", ['authenticatedAs' => 3]))->getStatusCode());
    }

    #[Test]
    public function hidden_recipient_groups_are_not_disclosed_to_other_participants(): void
    {
        $this->prepareDatabase([Group::class => [['id' => 4, 'is_hidden' => true]]]);
        $id = $this->dialogId($this->start());
        $response = $this->send($this->request('GET', "/api/dialogs/$id", ['authenticatedAs' => 3])->withQueryParams(['include' => 'groups,users.groups']));
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertSame([], $data['data']['relationships']['groups']['data']);
        $this->assertStringNotContainsString('Mods', $data['data']['attributes']['title']);
        $this->assertNotContains('4', collect($data['included'] ?? [])->where('type', 'groups')->pluck('id')->all());
    }

    #[Test]
    public function empty_groups_keep_the_conversation_after_the_sender_leaves(): void
    {
        $this->prepareDatabase(['group_user' => []]);
        $id = $this->dialogId($this->start());
        $this->database()->table('group_user')->where('user_id', 3)->delete();
        $this->assertSame(204, $this->send($this->request('POST', "/api/dialogs/$id/leave", ['authenticatedAs' => 1]))->getStatusCode());
        $this->assertTrue(Dialog::whereKey($id)->exists());
        $this->database()->table('group_user')->insert(['user_id' => 3, 'group_id' => 4]);
        $this->assertSame(200, $this->send($this->request('GET', "/api/dialogs/$id", ['authenticatedAs' => 3]))->getStatusCode());
    }

    #[Test]
    public function an_admin_can_grant_the_group_messaging_permission_to_members(): void
    {
        $this->prepareDatabase(['group_permission' => [['group_id' => 3, 'permission' => 'dialog.messageGroups']]]);
        $this->dialogId($this->start(actor: 3));
    }

    #[Test]
    public function disabling_a_recipient_group_prevents_new_conversations_without_revoking_existing_access(): void
    {
        $id = $this->dialogId($this->start());
        resolve(SettingsRepositoryInterface::class)->set('flarum-messages.recipient_groups', '[]');
        $this->assertSame(422, $this->start()->getStatusCode());
        $this->assertSame(200, $this->send($this->request('GET', "/api/dialogs/$id", ['authenticatedAs' => 3]))->getStatusCode());
    }

    #[Test]
    public function email_subject_counts_the_deduplicated_group_audience(): void
    {
        $this->prepareDatabase([
            User::class => [['id' => 4, 'username' => 'bob', 'email' => 'bob@machine.local', 'is_email_confirmed' => 1, 'preferences' => '{"notify_messageReceived_email":false}']],
            'group_user' => [['user_id' => 4, 'group_id' => 4]],
        ]);
        $this->captureSentMail();
        $alice = User::findOrFail(3);
        $alice->preferences = [];
        $alice->save();
        $this->dialogId($this->start(users: [3]));
        $this->assertSame(1, $this->emailsTo('alice@machine.local'));
        $email = $this->sent[0];
        $this->assertStringContainsString('1 other', $email->getSubject());
    }

    #[Test]
    public function the_first_read_response_returns_the_saved_position_for_a_group_only_member(): void
    {
        $id = $this->dialogId($this->start());
        $messageId = Dialog::findOrFail($id)->last_message_id;
        $response = $this->send($this->request('PATCH', "/api/dialogs/$id", [
            'authenticatedAs' => 3,
            'json' => ['data' => ['type' => 'dialogs', 'id' => $id, 'attributes' => ['lastReadMessageId' => $messageId]]],
        ]));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($messageId, json_decode((string) $response->getBody(), true)['data']['attributes']['lastReadMessageId']);
    }

    #[Test]
    public function empty_groups_still_count_towards_the_new_conversation_limit(): void
    {
        $dialogs = $messages = [];
        $at = Carbon::now()->subSeconds(20);
        foreach (range(50, 59) as $id) {
            $dialogs[] = ['id' => $id, 'type' => 'group', 'first_message_id' => $id, 'last_message_id' => $id, 'recipient_count' => 0, 'created_at' => $at];
            $messages[] = ['id' => $id, 'dialog_id' => $id, 'user_id' => 3, 'number' => 1, 'content' => '<t><p>Hello</p></t>', 'created_at' => $at];
        }
        $this->prepareDatabase([
            Dialog::class => $dialogs,
            DialogMessage::class => $messages,
            'group_permission' => [['group_id' => 3, 'permission' => 'dialog.messageGroups']],
        ]);
        $this->database()->table('group_user')->where('user_id', 3)->delete();
        $response = $this->start(actor: 3);
        $this->assertSame(429, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function joining_a_group_does_not_receive_messages_queued_before_joining(): void
    {
        $this->captureSentMail();
        $alice = User::findOrFail(3);
        $alice->preferences = [];
        $alice->save();
        $this->database()->table('group_user')->where('user_id', 3)->delete();
        $container = $this->app()->getContainer();
        $queue = $container->make(Queue::class);
        $container->instance(Queue::class, $this->createStub(Queue::class));
        $id = $this->dialogId($this->start());
        $job = new SendMessageNotificationsJob(DialogMessage::where('dialog_id', $id)->firstOrFail());
        $this->database()->table('group_user')->insert(['user_id' => 3, 'group_id' => 4]);
        $container->instance(Queue::class, $queue);
        $job->handle($container->make(NotificationSyncer::class));
        $this->assertSame(0, $this->emailsTo('alice@machine.local'));
    }

    #[Test]
    public function queued_email_is_cancelled_when_the_recipient_loses_access_before_transport(): void
    {
        $this->captureSentMail();
        $alice = User::findOrFail(3);
        $alice->preferences = [];
        $alice->save();
        $container = $this->app()->getContainer();
        $jobs = [];
        $queue = $this->createStub(Queue::class);
        $queue->method('push')->willReturnCallback(function ($job) use (&$jobs) { $jobs[] = $job; });
        $container->instance(Queue::class, $queue);
        $this->dialogId($this->start());
        $notifications = array_shift($jobs);
        $this->assertInstanceOf(SendMessageNotificationsJob::class, $notifications);
        $notifications->handle($container->make(NotificationSyncer::class));
        $this->assertCount(1, $jobs);
        $this->assertInstanceOf(SendEmailNotificationJob::class, $jobs[0]);
        $this->database()->table('group_user')->where('user_id', 3)->delete();
        $jobs[0]->handle($container->make(NotificationMailer::class), $container->make(CacheRepository::class));
        $this->assertSame(0, $this->emailsTo('alice@machine.local'));
    }

    #[Test]
    public function rolling_back_does_not_turn_group_read_state_into_individual_membership(): void
    {
        $id = $this->dialogId($this->start());
        if (! in_array($this->database()->getDriverName(), ['sqlite', 'pgsql'], true)) {
            $this->markTestSkipped('Historical schema tests need transactional DDL.');
        }
        $this->assertTrue($this->database()->table('dialog_user')->where('dialog_id', $id)->where('user_id', 3)->exists());
        $migration = include __DIR__.'/../../../../migrations/2026_11_08_000000_add_group_recipients.php';
        $migration['down']($this->database()->getSchemaBuilder());
        $this->assertEquals([1], $this->database()->table('dialog_user')->where('dialog_id', $id)->pluck('user_id')->all());
    }

    #[Test]
    public function out_of_order_notification_jobs_only_email_a_group_member_once_until_read(): void
    {
        $this->captureSentMail();
        $alice = User::findOrFail(3);
        $alice->preferences = [];
        $alice->save();
        $container = $this->app()->getContainer();
        $realQueue = $container->make(Queue::class);
        $jobs = [];
        $queue = $this->createStub(Queue::class);
        $queue->method('push')->willReturnCallback(function ($job) use (&$jobs) { $jobs[] = $job; });
        $container->instance(Queue::class, $queue);
        $id = $this->dialogId($this->start());
        $this->assertSame(201, $this->reply($id, 1)->getStatusCode());
        $this->assertCount(2, $jobs);
        $container->instance(Queue::class, $realQueue);
        $syncer = $container->make(NotificationSyncer::class);
        $jobs[1]->handle($syncer);
        $jobs[0]->handle($syncer);
        $this->assertSame(1, $this->emailsTo('alice@machine.local'));
    }

    #[Test]
    public function notification_job_can_retry_after_claiming_a_group_member(): void
    {
        $this->captureSentMail();
        $alice = User::findOrFail(3);
        $alice->preferences = [];
        $alice->save();
        $container = $this->app()->getContainer();
        $realQueue = $container->make(Queue::class);
        $container->instance(Queue::class, $this->createStub(Queue::class));
        $id = $this->dialogId($this->start());
        $job = new SendMessageNotificationsJob(DialogMessage::where('dialog_id', $id)->firstOrFail());
        $queue = $this->createStub(Queue::class);
        $queue->method('push')->willThrowException(new \RuntimeException('Queue unavailable'));
        $container->instance(Queue::class, $queue);

        try {
            $job->handle($container->make(NotificationSyncer::class));
            $this->fail('The queue failure must interrupt notification delivery.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Queue unavailable', $error->getMessage());
        }
        $container->instance(Queue::class, $realQueue);
        $job->handle($container->make(NotificationSyncer::class));
        $this->assertSame(1, $this->emailsTo('alice@machine.local'));
    }

    #[Test]
    public function large_group_notifications_stay_within_database_parameter_limits(): void
    {
        $this->app();
        $users = $members = [];
        foreach (range(1000, 9999) as $id) {
            $users[] = $this->throughFactory(User::class, ['id' => $id, 'username' => "member$id", 'email' => "member$id@machine.local", 'preferences' => ['notify_messageReceived_email' => false]]);
            $members[] = ['user_id' => $id, 'group_id' => 4];
        }
        foreach (array_chunk($users, 500) as $chunk) {
            $this->database()->table('users')->insert($chunk);
        }
        foreach (array_chunk($members, 500) as $chunk) {
            $this->database()->table('group_user')->insert($chunk);
        }
        $jobs = [];
        $queue = $this->createStub(Queue::class);
        $queue->method('push')->willReturnCallback(function ($job) use (&$jobs) { $jobs[] = $job; });
        $container = $this->app()->getContainer();
        $container->instance(Queue::class, $queue);
        $id = $this->dialogId($this->start());
        $maxBindings = 0;
        $container->make('events')->listen(\Illuminate\Database\Events\QueryExecuted::class, function ($query) use (&$maxBindings) {
            $maxBindings = max($maxBindings, count($query->bindings));
        });
        $jobs[0]->handle($container->make(NotificationSyncer::class));
        $this->assertSame(9002, $this->database()->table('dialog_user')->where('dialog_id', $id)->count());
        // SQLite's standard ceiling is lower than native MySQL/PostgreSQL prepares.
        $this->assertGreaterThan(0, $maxBindings);
        $this->assertLessThanOrEqual(32766, $maxBindings);
    }

    public static function typingRevocations(): array
    {
        return ['recipient' => ['recipient'], 'sender' => ['sender'], 'hidden recipient' => ['hidden'], 'identified permission' => ['permission']];
    }

    #[Test]
    #[DataProvider('typingRevocations')]
    public function typing_stops_after_access_is_revoked_on_an_existing_socket(string $scenario): void
    {
        $hidden = in_array($scenario, ['hidden', 'permission'], true);
        if ($hidden) {
            $this->prepareDatabase(['group_permission' => [['group_id' => 4, 'permission' => 'user.viewLastSeenAt']]]);
        }
        $id = $this->dialogId($this->start(users: $scenario === 'permission' ? [3] : []));
        $this->assertSame(200, $this->send($this->request('GET', "/api/dialogs/$id", ['authenticatedAs' => 3]))->getStatusCode());
        if ($hidden) {
            $senderUser = User::findOrFail(1);
            $senderUser->setPreference('discloseOnline', false);
            $senderUser->save();
        }
        $config = new Config(['url' => 'http://localhost', 'database' => ['password' => 'test-only']]);
        $settings = new WebsocketSettings($config);
        $manager = new ChannelManager($settings, $config);
        $container = $this->app()->getContainer();
        $container->instance(WebsocketSettings::class, $settings);
        $container->instance(ChannelManager::class, $manager);
        $socket = fn ($id) => new class($id) implements ConnectionInterface {
            public array $sent = [];

            public function __construct(public string $socketId)
            {
            }

            public function send($data): void
            {
                $this->sent[] = json_decode($data, true);
            }

            public function close(): void
            {
            }
        };
        $sender = $socket('1.1');
        $recipient = $socket('3.3');
        $channel = "private-privateMessageTyping=$id";
        foreach ([[$sender, 'private-user=1'], [$recipient, 'private-user=3'], [$sender, $channel], [$recipient, $channel]] as [$connection, $name]) {
            $manager->subscribeToChannel($connection, $name, (object) [
                'auth' => $settings->appKey.':'.hash_hmac('sha256', $connection->socketId.':'.$name, $settings->appSecret),
            ]);
        }
        if ($hidden) {
            $identified = "private-privateMessageTypingIdentified=$id";
            $manager->subscribeToChannel($recipient, $identified, (object) [
                'auth' => $settings->appKey.':'.hash_hmac('sha256', $recipient->socketId.':'.$identified, $settings->appSecret),
            ]);
        }
        $recipient->sent = [];
        $typing = (object) ['event' => 'client-typing', 'channel' => $channel, 'data' => (object) ['userId' => 999]];
        (new WebsocketMessage($typing, $sender, $manager))->respond();
        $this->assertCount(1, $recipient->sent);
        $this->assertSame(1, $recipient->sent[0]['data']['userId']);
        $recipient->sent = [];
        if ($scenario === 'sender') {
            $this->database()->table('dialog_user')->where('dialog_id', $id)->where('user_id', 1)->delete();
            $this->assertSame(404, $this->send($this->request('GET', "/api/dialogs/$id", ['authenticatedAs' => 1]))->getStatusCode());
        } else {
            $this->database()->table('group_user')->where('user_id', 3)->delete();
            $this->assertSame($scenario === 'permission' ? 200 : 404, $this->send($this->request('GET', "/api/dialogs/$id", ['authenticatedAs' => 3]))->getStatusCode());
        }
        (new WebsocketMessage($typing, $sender, $manager))->respond();
        $this->assertSame([], $recipient->sent, 'The socket still receives typing activity after authorization was revoked.');
    }
}
