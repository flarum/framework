<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration;

use Carbon\Carbon;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Messages\UserDialogState;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * A member leaving the forum doesn't take the other side of their
 * conversations with them. Their messages stay, with no author, as posts do;
 * previously the database cascade deleted them, left the dialog pointing at
 * nothing, and the title field then dereferenced a recipient that was gone.
 */
class UserDeletionTest extends TestCase
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
            ],
            Dialog::class => [
                ['id' => 102, 'type' => 'direct', 'first_message_id' => 102, 'last_message_id' => 103, 'last_message_at' => Carbon::now(), 'last_message_user_id' => 3],
                // A conversation with nothing in it yet.
                ['id' => 103, 'type' => 'direct'],
            ],
            DialogMessage::class => [
                ['id' => 102, 'dialog_id' => 102, 'user_id' => 4, 'content' => 'Hi Alice', 'number' => 1, 'ip_address' => '203.0.113.9'],
                ['id' => 103, 'dialog_id' => 102, 'user_id' => 3, 'content' => 'Hi Bob', 'number' => 2],
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
    public function deleting_a_member_keeps_the_conversation_for_the_other(): void
    {
        $this->app();

        User::find(4)->delete();

        $this->assertNull(DialogMessage::find(102)->user_id, "Bob's message stays, without an author.");
        $this->assertEquals(102, Dialog::find(102)->first_message_id);

        $response = $this->send($this->request('GET', '/api/dialogs/102', ['authenticatedAs' => 3]));
        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $body);
        // Tests run without locales, so the title is its key; the point is
        // that building it no longer dereferences a recipient who is gone.
        $this->assertIsString(json_decode($body, true)['data']['attributes']['title']);

        $response = $this->send(
            $this->request('GET', '/api/dialog-messages', ['authenticatedAs' => 3])->withQueryParams(['filter' => ['dialog' => '102']])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, json_decode($response->getBody()->getContents(), true)['data']);
    }

    #[Test]
    public function marking_everything_read_copes_with_a_conversation_that_has_no_messages_yet(): void
    {
        $response = $this->send($this->request('POST', '/api/dialogs/read', ['authenticatedAs' => 3]));

        $this->assertEquals(204, $response->getStatusCode(), $response->getBody()->getContents());

        $this->assertEquals(103, UserDialogState::where('dialog_id', 102)->where('user_id', 3)->value('last_read_message_id'));
        $this->assertEquals(0, UserDialogState::where('dialog_id', 103)->where('user_id', 3)->value('last_read_message_id'));
    }
}
