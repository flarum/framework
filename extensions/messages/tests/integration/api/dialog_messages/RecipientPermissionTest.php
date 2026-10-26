<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration\api\dialog_messages;

use Carbon\Carbon;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Members can't message someone who can't message back. Holders of the
 * "Message users without messaging permission" permission can (admins always,
 * any other group once it's granted), and the person they message can then
 * reply in that conversation.
 */
class RecipientPermissionTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages');

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'alice', 'email' => 'alice@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'bob', 'email' => 'bob@machine.local', 'is_email_confirmed' => 1],
                // Unconfirmed, so only the Guest group's permissions: she can't send messages.
                ['id' => 5, 'username' => 'carol', 'email' => 'carol@machine.local', 'is_email_confirmed' => 0],
                ['id' => 6, 'username' => 'moderator', 'email' => 'moderator@machine.local', 'is_email_confirmed' => 1],
            ],
            'group_user' => [
                ['user_id' => 6, 'group_id' => 4],
            ],
            // Granted to no group by default; this forum gives it to its moderators.
            'group_permission' => [
                ['permission' => 'dialog.messageUsersWithoutPermission', 'group_id' => 4],
            ],
            // A conversation from an hour ago, before carol lost the ability to send.
            Dialog::class => [
                ['id' => 201, 'type' => 'direct', 'first_message_id' => 201, 'last_message_id' => 201, 'last_message_at' => Carbon::now()->subHour(), 'created_at' => Carbon::now()->subHour()],
            ],
            DialogMessage::class => [
                ['id' => 201, 'dialog_id' => 201, 'user_id' => 3, 'content' => '<t><p>Hi Carol</p></t>', 'number' => 1, 'created_at' => Carbon::now()->subHour()],
            ],
            'dialog_user' => [
                ['dialog_id' => 201, 'user_id' => 3, 'joined_at' => Carbon::now(), 'last_read_message_id' => 201],
                ['dialog_id' => 201, 'user_id' => 5, 'joined_at' => Carbon::now()],
            ],
        ]);
    }

    protected function sendMessage(int $from, ?int $to = null, ?int $dialogId = null): array
    {
        $data = ['type' => 'dialog-messages', 'attributes' => ['content' => 'Hello']];

        if ($dialogId) {
            $data['relationships'] = ['dialog' => ['data' => ['type' => 'dialogs', 'id' => (string) $dialogId]]];
        } else {
            $data['attributes']['users'] = [['id' => $to]];
        }

        $response = $this->send($this->request('POST', '/api/dialog-messages', ['authenticatedAs' => $from, 'json' => ['data' => $data]]));

        return [$response->getStatusCode(), json_decode((string) $response->getBody(), true)];
    }

    protected function dialogIdOf(array $body): int
    {
        return (int) $body['data']['relationships']['dialog']['data']['id'];
    }

    protected function showDialog(int $as, int $dialogId): array
    {
        $response = $this->send($this->request('GET', "/api/dialogs/$dialogId", ['authenticatedAs' => $as]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        return json_decode((string) $response->getBody(), true)['data']['attributes'];
    }

    #[Test]
    public function a_member_cannot_start_a_conversation_with_someone_who_cannot_send_messages(): void
    {
        [$status, $body] = $this->sendMessage(3, to: 5);

        $this->assertEquals(422, $status, json_encode($body));
        $this->assertEquals('/data/attributes/users', $body['errors'][0]['source']['pointer'] ?? null, json_encode($body));
        $this->assertSame(1, DialogMessage::query()->where('dialog_id', 201)->count());
    }

    #[Test]
    public function a_member_cannot_keep_messaging_them_in_an_existing_conversation(): void
    {
        [$status, $body] = $this->sendMessage(3, dialogId: 201);

        $this->assertEquals(403, $status, json_encode($body));
    }

    #[Test]
    public function a_moderator_can_message_them_and_the_conversation_is_opened_to_replies(): void
    {
        [$status, $body] = $this->sendMessage(6, to: 5);

        $this->assertEquals(201, $status, json_encode($body));
        $this->assertTrue(Dialog::query()->find($this->dialogIdOf($body))->anyone_can_reply);
    }

    #[Test]
    public function an_admin_can_message_them_without_the_permission_being_granted(): void
    {
        [$status, $body] = $this->sendMessage(1, to: 5);

        $this->assertEquals(201, $status, json_encode($body));
        $this->assertTrue(Dialog::query()->find($this->dialogIdOf($body))->anyone_can_reply);
    }

    #[Test]
    public function they_can_reply_in_the_conversation_a_moderator_opened(): void
    {
        [, $body] = $this->sendMessage(6, to: 5);

        [$status, $reply] = $this->sendMessage(5, dialogId: $this->dialogIdOf($body));

        $this->assertEquals(201, $status, json_encode($reply));
    }

    #[Test]
    public function they_still_cannot_start_conversations_of_their_own(): void
    {
        $this->sendMessage(6, to: 5);

        [$status, $body] = $this->sendMessage(5, to: 4);

        $this->assertEquals(403, $status, json_encode($body));
    }

    #[Test]
    public function a_member_can_continue_a_conversation_already_opened_to_replies(): void
    {
        $this->app();
        Dialog::query()->where('id', 201)->update(['anyone_can_reply' => true]);

        [$status, $body] = $this->sendMessage(3, to: 5);

        $this->assertEquals(201, $status, json_encode($body));
        $this->assertSame(201, $this->dialogIdOf($body));
    }

    #[Test]
    public function a_moderator_messaging_a_member_also_opens_the_conversation(): void
    {
        [$status, $body] = $this->sendMessage(6, to: 3);

        $this->assertEquals(201, $status, json_encode($body));
        $this->assertTrue(Dialog::query()->find($this->dialogIdOf($body))->anyone_can_reply);
    }

    #[Test]
    public function conversations_between_members_are_unchanged(): void
    {
        [$status, $body] = $this->sendMessage(3, to: 4);

        $this->assertEquals(201, $status, json_encode($body));
        $this->assertFalse(Dialog::query()->find($this->dialogIdOf($body))->anyone_can_reply);

        [$status, $reply] = $this->sendMessage(4, dialogId: $this->dialogIdOf($body));

        $this->assertEquals(201, $status, json_encode($reply));
    }

    #[Test]
    public function the_conversation_says_whether_its_viewer_can_send(): void
    {
        [, $body] = $this->sendMessage(6, to: 5);
        $opened = $this->dialogIdOf($body);

        $this->assertTrue($this->showDialog(5, $opened)['canSendMessage']);
        $this->assertTrue($this->showDialog(5, $opened)['anyoneCanReply']);

        $this->assertFalse($this->showDialog(5, 201)['canSendMessage']);
        $this->assertFalse($this->showDialog(3, 201)['canSendMessage']);
        $this->assertFalse($this->showDialog(3, 201)['anyoneCanReply']);
    }

    #[Test]
    public function the_conversation_list_says_it_too(): void
    {
        $response = $this->send($this->request('GET', '/api/dialogs', ['authenticatedAs' => 3]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $dialogs = collect(json_decode((string) $response->getBody(), true)['data'])->keyBy('id');

        $this->assertFalse($dialogs['201']['attributes']['canSendMessage']);
    }

    #[Test]
    public function members_know_whether_they_can_message_users_without_messaging_permission(): void
    {
        $moderator = $this->send($this->request('GET', '/api/users/6', ['authenticatedAs' => 6]));
        $member = $this->send($this->request('GET', '/api/users/3', ['authenticatedAs' => 3]));

        $this->assertTrue(json_decode((string) $moderator->getBody(), true)['data']['attributes']['canMessageUsersWithoutPermission']);
        $this->assertFalse(json_decode((string) $member->getBody(), true)['data']['attributes']['canMessageUsersWithoutPermission']);
    }

    #[Test]
    public function a_suspended_member_can_reply_to_a_moderator_but_members_cannot_message_them(): void
    {
        $this->extension('flarum-suspend', 'flarum-messages');

        $this->prepareDatabase([
            User::class => [
                ['id' => 7, 'username' => 'dave', 'email' => 'dave@machine.local', 'is_email_confirmed' => 1, 'suspended_until' => Carbon::now()->addDay()],
            ],
        ]);

        [$status, $body] = $this->sendMessage(3, to: 7);
        $this->assertEquals(422, $status, json_encode($body));

        [$status, $body] = $this->sendMessage(6, to: 7);
        $this->assertEquals(201, $status, json_encode($body));

        [$status, $reply] = $this->sendMessage(7, dialogId: $this->dialogIdOf($body));
        $this->assertEquals(201, $status, json_encode($reply));
    }
}
