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
 * An account anonymised by flarum/gdpr is what's left of a member who asked
 * to be forgotten: nobody, admins included, can message it, in a new
 * conversation or one they already had.
 */
class AnonymizedRecipientTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages', 'flarum-gdpr');

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'alice', 'email' => 'alice@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'bob', 'email' => 'bob@machine.local', 'is_email_confirmed' => 1],
                ['id' => 5, 'username' => 'Anonymous1', 'email' => 'Anonymous1@flarum-gdpr.local', 'is_email_confirmed' => 0, 'anonymized' => 1],
            ],
            // Conversations from before the member was anonymised, both opened
            // to replies: one with an admin, one with alice.
            Dialog::class => [
                ['id' => 301, 'type' => 'direct', 'anyone_can_reply' => true, 'first_message_id' => 301, 'last_message_id' => 301, 'last_message_at' => Carbon::now()->subHour(), 'created_at' => Carbon::now()->subHour()],
                ['id' => 302, 'type' => 'direct', 'anyone_can_reply' => true, 'first_message_id' => 302, 'last_message_id' => 302, 'last_message_at' => Carbon::now()->subHour(), 'created_at' => Carbon::now()->subHour()],
            ],
            DialogMessage::class => [
                ['id' => 301, 'dialog_id' => 301, 'user_id' => 1, 'content' => '<t><p>Hi</p></t>', 'number' => 1, 'created_at' => Carbon::now()->subHour()],
                ['id' => 302, 'dialog_id' => 302, 'user_id' => 3, 'content' => '<t><p>Hi</p></t>', 'number' => 1, 'created_at' => Carbon::now()->subHour()],
            ],
            'dialog_user' => [
                ['dialog_id' => 301, 'user_id' => 1, 'joined_at' => Carbon::now()->subHour(), 'last_read_message_id' => 301],
                ['dialog_id' => 301, 'user_id' => 5, 'joined_at' => Carbon::now()->subHour()],
                ['dialog_id' => 302, 'user_id' => 3, 'joined_at' => Carbon::now()->subHour(), 'last_read_message_id' => 302],
                ['dialog_id' => 302, 'user_id' => 5, 'joined_at' => Carbon::now()->subHour()],
            ],
        ]);
    }

    protected function startConversation(int $from, int $to): array
    {
        $response = $this->send($this->request('POST', '/api/dialog-messages', [
            'authenticatedAs' => $from,
            'json' => ['data' => ['type' => 'dialog-messages', 'attributes' => ['content' => 'Hello', 'users' => [['id' => $to]]]]],
        ]));

        return [$response->getStatusCode(), json_decode((string) $response->getBody(), true)];
    }

    protected function sendIn(int $from, int $dialogId): int
    {
        return $this->send($this->request('POST', '/api/dialog-messages', [
            'authenticatedAs' => $from,
            'json' => ['data' => [
                'type' => 'dialog-messages',
                'attributes' => ['content' => 'Hello'],
                'relationships' => ['dialog' => ['data' => ['type' => 'dialogs', 'id' => (string) $dialogId]]],
            ]],
        ]))->getStatusCode();
    }

    /**
     * @return array<string, bool|null> Each listed user's `canMessage`, by id.
     */
    protected function canMessageInUserList(int $as): array
    {
        $response = $this->send($this->request('GET', '/api/users', ['authenticatedAs' => $as]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $users = json_decode((string) $response->getBody(), true)['data'];

        return array_combine(array_column($users, 'id'), array_map(fn (array $user) => $user['attributes']['canMessage'] ?? null, $users));
    }

    #[Test]
    public function a_member_cannot_start_a_conversation_with_an_anonymized_user(): void
    {
        [$status, $body] = $this->startConversation(3, to: 5);

        $this->assertEquals(422, $status, json_encode($body));
        $this->assertEquals('/data/attributes/users', $body['errors'][0]['source']['pointer'] ?? null, json_encode($body));
        $this->assertSame(2, Dialog::query()->count());
    }

    #[Test]
    public function nor_can_an_admin(): void
    {
        [$status, $body] = $this->startConversation(1, to: 5);

        $this->assertEquals(422, $status, json_encode($body));
        $this->assertSame(2, Dialog::query()->count());
    }

    #[Test]
    public function an_admin_cannot_keep_messaging_them_in_a_conversation_they_already_had(): void
    {
        $this->assertEquals(403, $this->sendIn(1, 301));
        $this->assertSame(1, DialogMessage::query()->where('dialog_id', 301)->count());
    }

    #[Test]
    public function nor_can_a_member_in_a_conversation_opened_to_replies(): void
    {
        $this->assertEquals(403, $this->sendIn(3, 302));
    }

    #[Test]
    public function the_conversation_tells_the_admin_they_cannot_send(): void
    {
        $response = $this->send($this->request('GET', '/api/dialogs/301', ['authenticatedAs' => 1]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertFalse(json_decode((string) $response->getBody(), true)['data']['attributes']['canSendMessage']);
    }

    #[Test]
    public function members_can_still_message_everyone_else(): void
    {
        [$status, $body] = $this->startConversation(3, to: 4);

        $this->assertEquals(201, $status, json_encode($body));
    }

    #[Test]
    public function user_lists_tell_a_member_who_cannot_be_messaged(): void
    {
        $canMessage = $this->canMessageInUserList(3);

        $this->assertTrue($canMessage['4']);
        $this->assertFalse($canMessage['5']);
    }

    #[Test]
    public function user_lists_tell_an_admin_too(): void
    {
        $canMessage = $this->canMessageInUserList(1);

        $this->assertTrue($canMessage['4']);
        $this->assertFalse($canMessage['5']);
    }
}
