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

class ListBehaviourTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages');

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'alice'],
                ['id' => 4, 'username' => 'bob'],
                ['id' => 5, 'username' => 'carol'],
            ],
            Dialog::class => [
                ['id' => 102, 'type' => 'direct', 'first_message_id' => 102, 'last_message_id' => 103, 'last_message_at' => Carbon::now()->subDay(), 'created_at' => Carbon::now()->subDays(2)],
                ['id' => 103, 'type' => 'direct', 'first_message_id' => 104, 'last_message_id' => 104, 'last_message_at' => Carbon::now(), 'created_at' => Carbon::now()->subDays(3)],
            ],
            DialogMessage::class => [
                ['id' => 102, 'dialog_id' => 102, 'user_id' => 4, 'content' => 'Hi Alice', 'number' => 1],
                ['id' => 103, 'dialog_id' => 102, 'user_id' => 3, 'content' => 'Hi Bob', 'number' => 2],
                ['id' => 104, 'dialog_id' => 103, 'user_id' => 5, 'content' => 'Hi from Carol', 'number' => 1],
            ],
            'dialog_user' => [
                // Alice has read all of 102 and none of 103.
                ['dialog_id' => 102, 'user_id' => 3, 'joined_at' => Carbon::now(), 'last_read_message_id' => 103],
                ['dialog_id' => 102, 'user_id' => 4, 'joined_at' => Carbon::now()],
                ['dialog_id' => 103, 'user_id' => 3, 'joined_at' => Carbon::now()],
                ['dialog_id' => 103, 'user_id' => 5, 'joined_at' => Carbon::now()],
            ],
        ]);
    }

    private function ids(array $query = []): array
    {
        $response = $this->send($this->request('GET', '/api/dialogs', ['authenticatedAs' => 3])->withQueryParams($query));

        $this->assertEquals(200, $response->getStatusCode());

        return array_column(json_decode($response->getBody()->getContents(), true)['data'], 'id');
    }

    #[Test]
    public function lists_the_most_recent_activity_first_by_default(): void
    {
        $this->assertEquals(['103', '102'], $this->ids());
    }

    #[Test]
    public function links_the_first_and_last_messages_without_being_asked_to_include_them(): void
    {
        $response = $this->send($this->request('GET', '/api/dialogs/102', ['authenticatedAs' => 3]));
        $relationships = json_decode($response->getBody()->getContents(), true)['data']['relationships'];

        $this->assertEquals('102', $relationships['firstMessage']['data']['id']);
        $this->assertEquals('103', $relationships['lastMessage']['data']['id']);
    }

    #[Test]
    public function can_ask_for_unread_conversations_and_for_read_ones(): void
    {
        $this->assertEquals(['103'], $this->ids(['filter' => ['unread' => '1']]));
        $this->assertEquals(['102'], $this->ids(['filter' => ['-unread' => '1']]));
    }

    #[Test]
    public function cannot_be_marked_read_past_its_last_message(): void
    {
        $response = $this->send(
            $this->request('PATCH', '/api/dialogs/103', [
                'authenticatedAs' => 3,
                'json' => ['data' => ['type' => 'dialogs', 'id' => '103', 'attributes' => ['lastReadMessageId' => 999999]]],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());
        $this->assertEquals(104, UserDialogState::where('dialog_id', 103)->where('user_id', 3)->value('last_read_message_id'));
    }
}
