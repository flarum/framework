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
 * conversation can be deleted, in a way that can be undone. Judged from the dialog's own last-message pointer,
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

    protected function hide(int $id, int $actor): int
    {
        return $this->send($this->request('PATCH', '/api/dialog-messages/'.$id, [
            'authenticatedAs' => $actor,
            'json' => ['data' => ['type' => 'dialog-messages', 'id' => (string) $id, 'attributes' => ['isHidden' => true]]],
        ]))->getStatusCode();
    }

    #[Test]
    public function the_latest_message_can_be_deleted_by_its_author_but_an_earlier_one_cannot(): void
    {
        // Bob's message has been replied to, so it stays; Alice's is the latest.
        $this->assertEquals(403, $this->hide(102, 4));
        $this->assertEquals(200, $this->hide(103, 3));
    }

    /**
     * Deleting for good is a permission of its own: the window for members'
     * own messages doesn't reach it.
     */
    #[Test]
    public function members_cannot_delete_their_own_messages_for_good(): void
    {
        $this->setting('flarum-messages.allow_delete_own_messages', '-1');

        $this->assertEquals(403, $this->send($this->request('DELETE', '/api/dialog-messages/103', ['authenticatedAs' => 3]))->getStatusCode());
        $this->assertNotNull($this->database()->table('dialog_messages')->where('id', 103)->first());
    }

    /**
     * A notice records something that happened, such as who renamed a group:
     * it isn't its member's to take back, however their own messages may be.
     */
    #[Test]
    public function a_notice_cannot_be_deleted_by_the_member_it_is_about(): void
    {
        $this->setting('flarum-messages.allow_delete_own_messages', '-1');

        $this->prepareDatabase([
            DialogMessage::class => [
                ['id' => 105, 'dialog_id' => 102, 'user_id' => 3, 'type' => 'renamed', 'content' => '{"name":"Hi"}', 'number' => 3],
            ],
        ]);

        $this->assertEquals(403, $this->hide(105, 3));
        $this->assertEquals(403, $this->send($this->request('DELETE', '/api/dialog-messages/105', ['authenticatedAs' => 3]))->getStatusCode());
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
