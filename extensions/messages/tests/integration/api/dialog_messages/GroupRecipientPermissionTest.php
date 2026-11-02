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
use Psr\Http\Message\ResponseInterface;

/**
 * Every recipient of a new group is held to the rules for a direct message,
 * and one who fails them stops the whole message: nothing is written.
 */
class GroupRecipientPermissionTest extends TestCase
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
            'group_permission' => [
                ['permission' => 'dialog.messageUsersWithoutPermission', 'group_id' => 4],
            ],
        ]);
    }

    protected function sendTo(array $recipients, int $actor = 3): ResponseInterface
    {
        return $this->send(
            $this->request('POST', '/api/dialog-messages', [
                'authenticatedAs' => $actor,
                'json' => [
                    'data' => [
                        'type' => 'dialog-messages',
                        'attributes' => [
                            'content' => 'Hello, everyone!',
                            'users' => array_map(fn (int $id) => ['id' => $id], $recipients),
                        ],
                    ],
                ],
            ])
        );
    }

    #[Test]
    public function one_recipient_who_does_not_exist_stops_the_message(): void
    {
        $response = $this->sendTo([4, 999]);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(0, Dialog::query()->count());
    }

    #[Test]
    public function one_recipient_who_cannot_reply_stops_the_message_and_is_named(): void
    {
        $response = $this->sendTo([4, 5]);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals("carol can't reply to messages.", json_decode((string) $response->getBody(), true)['errors'][0]['detail']);
        $this->assertEquals(0, Dialog::query()->count());
    }

    #[Test]
    public function someone_allowed_to_message_users_without_permission_can_include_them_and_the_group_is_opened(): void
    {
        $response = $this->sendTo([4, 5], actor: 6);

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());

        $dialog = Dialog::query()->sole();
        $this->assertEquals('group', $dialog->type);
        $this->assertTrue($dialog->anyone_can_reply);
    }

    /**
     * A direct conversation opened by a moderator lets its members reply in
     * it. It doesn't let one of them pull the other into a new group, which
     * nobody has opened.
     */
    #[Test]
    public function an_opened_direct_conversation_does_not_let_a_member_add_its_other_member_to_a_group(): void
    {
        $this->prepareDatabase([
            Dialog::class => [
                ['id' => 70, 'type' => 'direct', 'anyone_can_reply' => true, 'first_message_id' => 70, 'last_message_id' => 70, 'last_message_at' => Carbon::now()->subDay(), 'created_at' => Carbon::now()->subDay()],
            ],
            DialogMessage::class => [
                ['id' => 70, 'dialog_id' => 70, 'user_id' => 3, 'content' => '<t><p>Hi</p></t>', 'number' => 1, 'created_at' => Carbon::now()->subDay()],
            ],
            'dialog_user' => [
                ['dialog_id' => 70, 'user_id' => 3, 'joined_at' => Carbon::now()->subDay()],
                ['dialog_id' => 70, 'user_id' => 5, 'joined_at' => Carbon::now()->subDay()],
            ],
        ]);

        $response = $this->sendTo([4, 5]);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(0, Dialog::query()->where('type', 'group')->count());

        // While still being able to reply to her in their own conversation.
        $this->assertEquals(201, $this->sendTo([5])->getStatusCode());
    }
}
