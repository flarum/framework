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
 * "Delete own messages until someone replies": only the latest message in the
 * conversation can go. Judged from the dialog's own last-message pointer,
 * which the list already carries, rather than loading the last message for
 * every message shown (and crashing when there is none).
 */
class DeletePolicyTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages');

        $this->setting('flarum-messages.allow_delete_own_messages', 'reply');

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'alice'],
                ['id' => 4, 'username' => 'bob'],
            ],
            Dialog::class => [
                ['id' => 102, 'type' => 'direct', 'first_message_id' => 102, 'last_message_id' => 103, 'last_message_at' => Carbon::now()],
                ['id' => 103, 'type' => 'direct'],
            ],
            DialogMessage::class => [
                ['id' => 102, 'dialog_id' => 102, 'user_id' => 4, 'content' => 'Hi Alice', 'number' => 1],
                ['id' => 103, 'dialog_id' => 102, 'user_id' => 3, 'content' => 'Hi Bob', 'number' => 2],
                ['id' => 104, 'dialog_id' => 103, 'user_id' => 3, 'content' => 'Lost', 'number' => 1],
            ],
            'dialog_user' => [
                ['dialog_id' => 102, 'user_id' => 3, 'joined_at' => Carbon::now()],
                ['dialog_id' => 102, 'user_id' => 4, 'joined_at' => Carbon::now()],
                ['dialog_id' => 103, 'user_id' => 3, 'joined_at' => Carbon::now()],
                ['dialog_id' => 103, 'user_id' => 4, 'joined_at' => Carbon::now()],
            ],
        ]);
    }

    #[Test]
    public function the_latest_message_can_be_deleted_by_its_author_but_an_earlier_one_cannot(): void
    {
        // Bob's message has been replied to, so it stays; Alice's is the latest.
        $this->assertEquals(403, $this->send($this->request('DELETE', '/api/dialog-messages/102', ['authenticatedAs' => 4]))->getStatusCode());
        $this->assertEquals(204, $this->send($this->request('DELETE', '/api/dialog-messages/103', ['authenticatedAs' => 3]))->getStatusCode());
    }

    #[Test]
    public function a_dialog_whose_pointers_are_missing_does_not_break_listing_its_messages(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/dialog-messages', ['authenticatedAs' => 3])->withQueryParams(['filter' => ['dialog' => '103']])
        );

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());
    }

    #[Test]
    public function messages_can_be_listed_for_several_dialogs_at_once(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/dialog-messages', ['authenticatedAs' => 3])->withQueryParams(['filter' => ['dialog' => ['102', '103']]])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $this->assertEqualsCanonicalizing(['102', '103', '104'], array_column(json_decode($body, true)['data'], 'id'));
    }
}
