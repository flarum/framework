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
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Whether the other member of a conversation can reply is a question about
 * their permissions, which come from their groups, hidden ones included. Here
 * only staff may send messages, and the staff group is hidden from them.
 */
class HiddenGroupPermissionTest extends TestCase
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
            ],
            Group::class => [
                ['id' => 10, 'name_singular' => 'Staff', 'name_plural' => 'Staff', 'is_hidden' => 1],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 10],
                ['user_id' => 4, 'group_id' => 10],
            ],
            'group_permission' => [
                ['permission' => 'dialog.sendMessage', 'group_id' => 10],
            ],
            Dialog::class => [
                ['id' => 201, 'type' => 'direct', 'first_message_id' => 201, 'last_message_id' => 201, 'last_message_at' => Carbon::now()->subHour(), 'created_at' => Carbon::now()->subHour()],
            ],
            DialogMessage::class => [
                ['id' => 201, 'dialog_id' => 201, 'user_id' => 3, 'content' => '<t><p>Hi Bob</p></t>', 'number' => 1, 'created_at' => Carbon::now()->subHour()],
            ],
            'dialog_user' => [
                ['dialog_id' => 201, 'user_id' => 3, 'joined_at' => Carbon::now()->subHour(), 'last_read_message_id' => 201],
                ['dialog_id' => 201, 'user_id' => 4, 'joined_at' => Carbon::now()->subHour()],
            ],
        ]);
    }

    private function get(string $path): array
    {
        $this->app();

        // Members may send messages by default; on this forum only staff may.
        $this->database()->table('group_permission')
            ->where('permission', 'dialog.sendMessage')
            ->where('group_id', Group::MEMBER_ID)
            ->delete();

        $response = $this->send($this->request('GET', $path, ['authenticatedAs' => 3]));

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        return json_decode($body, true);
    }

    #[Test]
    public function a_conversation_with_someone_who_can_reply_through_a_hidden_group_can_be_written_in(): void
    {
        $this->assertTrue($this->get('/api/dialogs/201')['data']['attributes']['canSendMessage']);
    }

    #[Test]
    public function the_same_goes_for_the_conversation_list(): void
    {
        $dialogs = array_column($this->get('/api/dialogs')['data'], 'attributes', 'id');

        $this->assertTrue($dialogs['201']['canSendMessage']);
    }

    #[Test]
    public function they_are_shown_as_someone_who_can_be_messaged(): void
    {
        $this->assertTrue($this->get('/api/users/4')['data']['attributes']['canSendAnyMessage']);
    }
}
