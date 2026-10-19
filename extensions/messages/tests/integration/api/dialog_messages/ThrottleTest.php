<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration\api\dialog_messages;

use Carbon\Carbon;
use Flarum\Group\Group;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Messages\DialogMessageThrottler;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Sending is rate limited the way posting is: a member can't fire off messages
 * back to back, and can't open conversations with everyone on the forum in one
 * sitting. Without this, a new account could send unlimited emails, carrying
 * whatever it liked, to every user ID, from the forum's own address.
 */
class ThrottleTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'alice'],
                ['id' => 4, 'username' => 'bob'],
                ['id' => 5, 'username' => 'carol'],
            ],
            Dialog::class => [
                ['id' => 102, 'type' => 'direct'],
            ],
            DialogMessage::class => [
                ['id' => 102, 'dialog_id' => 102, 'user_id' => 4, 'content' => 'Hi', 'number' => 1, 'created_at' => Carbon::now()->subHour()],
            ],
            'dialog_user' => [
                ['dialog_id' => 102, 'user_id' => 3, 'joined_at' => Carbon::now()->subHour()],
                ['dialog_id' => 102, 'user_id' => 4, 'joined_at' => Carbon::now()->subHour()],
            ],
        ]);
    }

    private function post(int $as, array $attributes, ?int $dialogId = null)
    {
        $data = ['type' => 'dialog-messages', 'attributes' => $attributes];

        if ($dialogId) {
            $data['relationships'] = ['dialog' => ['data' => ['type' => 'dialogs', 'id' => (string) $dialogId]]];
        }

        return $this->send(
            $this->request('POST', '/api/dialog-messages', ['authenticatedAs' => $as, 'json' => ['data' => $data]])
        );
    }

    #[Test]
    public function a_message_straight_after_another_is_throttled(): void
    {
        $this->assertEquals(201, $this->post(3, ['content' => 'One'], 102)->getStatusCode());
        $this->assertEquals(429, $this->post(3, ['content' => 'Two'], 102)->getStatusCode());
    }

    #[Test]
    public function a_message_after_the_timeout_is_not(): void
    {
        $this->prepareDatabase([
            DialogMessage::class => [
                ['id' => 103, 'dialog_id' => 102, 'user_id' => 3, 'content' => 'Earlier', 'number' => 2, 'created_at' => Carbon::now()->subSeconds(DialogMessageThrottler::$timeout + 1)],
            ],
        ]);

        $this->assertEquals(201, $this->post(3, ['content' => 'Later'], 102)->getStatusCode());
    }

    #[Test]
    public function opening_too_many_conversations_in_an_hour_is_throttled_but_replying_is_not(): void
    {
        // Carol has started the hour's allowance of conversations already, each
        // long enough ago not to trip the per-message timeout.
        $dialogs = [];
        $messages = [];
        $members = [];

        for ($i = 0; $i < DialogMessageThrottler::$newDialogsPerHour; $i++) {
            $id = 200 + $i;
            $dialogs[] = ['id' => $id, 'type' => 'direct', 'created_at' => Carbon::now()->subMinutes(30)];
            $messages[] = ['id' => $id, 'dialog_id' => $id, 'user_id' => 5, 'content' => "Hello $i", 'number' => 1, 'created_at' => Carbon::now()->subMinutes(30)];
            $members[] = ['dialog_id' => $id, 'user_id' => 5, 'joined_at' => Carbon::now()->subMinutes(30)];
            $members[] = ['dialog_id' => $id, 'user_id' => 4, 'joined_at' => Carbon::now()->subMinutes(30)];
        }

        $this->prepareDatabase([Dialog::class => $dialogs, DialogMessage::class => $messages, 'dialog_user' => $members]);

        $this->assertEquals(429, $this->post(5, ['content' => 'One more', 'users' => [['id' => 3]]])->getStatusCode());

        // The limit is on new conversations, not on talking in existing ones.
        $this->assertEquals(201, $this->post(5, ['content' => 'Still here'], 200)->getStatusCode());
    }

    #[Test]
    public function a_member_allowed_to_send_without_throttling_is_not_throttled(): void
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'dialog.sendMessageWithoutThrottle', 'group_id' => Group::MEMBER_ID],
            ],
        ]);

        $this->assertEquals(201, $this->post(3, ['content' => 'One'], 102)->getStatusCode());
        $this->assertEquals(201, $this->post(3, ['content' => 'Two'], 102)->getStatusCode());
    }

    #[Test]
    public function other_endpoints_are_not_touched(): void
    {
        $this->assertEquals(201, $this->post(3, ['content' => 'One'], 102)->getStatusCode());

        $response = $this->send($this->request('GET', '/api/dialogs', ['authenticatedAs' => 3]));

        $this->assertEquals(200, $response->getStatusCode());
    }
}
